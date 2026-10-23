<?php

namespace App\Feeds;

use App\Models\BoardPost;
use App\Models\FeedSync;
use App\Models\Holiday;
use App\Models\InboxNotice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SharedFeeds
{
    public function tick(): void
    {
        $year = now(config('m04.timezone'))->year;
        foreach ([$year, $year + 1] as $y) {
            $this->holidays($y);
        }
        $this->board();
        $this->fanOut();
    }

    private function claim(string $key): ?string
    {
        return DB::transaction(function () use ($key): ?string {
            DB::table('feed_syncs')->insertOrIgnore(['key' => $key]);
            $sync = FeedSync::whereKey($key)->lockForUpdate()->firstOrFail();
            if ($sync->next_attempt_at?->gt(now()) || $sync->leased_until?->gt(now())) {
                return null;
            }
            $token = (string) Str::uuid();
            $sync->update(['last_attempt_at' => now(), 'leased_until' => now()->addMinutes(3), 'lease_token' => $token]);

            return $token;
        });
    }

    private function failed(string $key, string $token): void
    {
        DB::transaction(function () use ($key, $token): void {
            $sync = FeedSync::whereKey($key)->lockForUpdate()->firstOrFail();
            if ($sync->lease_token !== $token) {
                return;
            }
            $count = $sync->failures + 1;
            $sync->update(['failures' => $count, 'last_error' => 'source_unavailable_or_invalid', 'leased_until' => null, 'next_attempt_at' => now()->addHours(min(24, 2 ** min($count, 5)))]);
        });
    }

    public function holidays(int $year): bool
    {
        if ($year < 1900 || $year > 2199 || ! ($token = $this->claim('holidays:'.$year))) {
            return false;
        }
        $key = 'holidays:'.$year;
        try {
            $rows = json_decode(app(SourceHttp::class)->get('https://api.argentinadatos.com/v1/feriados/'.$year), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($rows) || ! array_is_list($rows) || count($rows) < 1 || count($rows) > 400) {
                throw new RuntimeException('holiday_contract');
            }
            $valid = [];
            foreach ($rows as $row) {
                if (! is_array($row) || ! is_string($row['fecha'] ?? null) || ! is_string($row['tipo'] ?? null) || ! is_string($row['nombre'] ?? null) || ! preg_match('/^'.$year.'-\d{2}-\d{2}$/', $row['fecha']) || ! trim($row['tipo']) || ! trim($row['nombre']) || mb_strlen($row['tipo']) > 100 || mb_strlen($row['nombre']) > 250) {
                    throw new RuntimeException('holiday_contract');
                }
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $row['fecha']);
                if ($date->format('Y-m-d') !== $row['fecha']) {
                    throw new RuntimeException('holiday_date');
                }
                $valid[] = ['year' => $year, 'date' => $row['fecha'], 'type' => $row['tipo'], 'name' => $row['nombre']];
            }

            return DB::transaction(function () use ($year, $valid, $key, $token): bool {
                $sync = FeedSync::whereKey($key)->lockForUpdate()->firstOrFail();
                if ($sync->lease_token !== $token) {
                    return false;
                }
                Holiday::where('year', $year)->delete();
                DB::table('holidays')->insertOrIgnore($valid);
                FeedSync::whereKey($key)->update(['last_success_at' => now(), 'next_attempt_at' => now()->addDays(15), 'leased_until' => null, 'failures' => 0, 'last_error' => null, 'lease_token' => null]);

                return true;
            });
        } catch (Throwable) {
            $this->failed($key, $token);

            return false;
        }
    }

    public function board(): bool
    {
        if (! ($token = $this->claim('board'))) {
            return false;
        }
        try {
            $posts = app(BoardParser::class)->parse(app(SourceHttp::class)->get(BoardParser::SOURCE));

            return DB::transaction(function () use ($posts, $token): bool {
                $sync = FeedSync::whereKey('board')->lockForUpdate()->firstOrFail();
                if ($sync->lease_token !== $token) {
                    return false;
                }
                $baseline = $sync->last_success_at === null;
                foreach ($posts as $data) {
                    $post = BoardPost::firstOrNew(['source_key' => $data['source_key']]);
                    $new = ! $post->exists;
                    $post->fill($data);
                    if ($new) {
                        $post->detected_at = now();
                    }
                    $post->save();
                    if ($new && ! $baseline) {
                        // Snapshot audience now: future accounts/enrollments never receive history.
                        DB::statement('INSERT INTO board_recipients (board_post_id, user_id) SELECT ?, u.id FROM users u WHERE u.email_verified_at IS NOT NULL AND EXISTS (SELECT 1 FROM academic_enrollments e WHERE e.user_id=u.id AND e.is_active=true AND e.completed_at IS NOT NULL) ON CONFLICT DO NOTHING', [$post->id]);
                    }
                }
                $local = CarbonImmutable::now(config('m04.timezone'));
                $next = $local->addDay()->setTime(8, 0);
                $sync->update(['last_success_at' => now(), 'next_attempt_at' => $next->utc(), 'leased_until' => null, 'failures' => 0, 'last_error' => null, 'lease_token' => null]);

                return true;
            });
        } catch (Throwable) {
            $this->failed('board', $token);

            return false;
        }
    }

    public function fanOut(): void
    {
        DB::table('board_recipients')->whereNull('delivered_at')->orderBy('id')->chunkById(100, function ($rows): void {
            $posts = BoardPost::whereIn('id', $rows->pluck('board_post_id')->unique())->get()->keyBy('id');
            foreach ($rows as $row) {
                DB::transaction(function () use ($row, $posts): void {
                    $recipient = DB::table('board_recipients')->where('id', $row->id)->lockForUpdate()->first();
                    if ($recipient->delivered_at) {
                        return;
                    }
                    $post = $posts->get($row->board_post_id);
                    if (! $post) {
                        return;
                    }
                    InboxNotice::firstOrCreate(['user_id' => $row->user_id, 'dedupe_key' => 'board:'.$post->id], ['type' => 'board', 'title' => $post->title, 'body' => mb_substr($post->excerpt, 0, 200), 'target_id' => $post->id]);
                    DB::table('board_recipients')->where('id', $row->id)->update(['delivered_at' => now()]);
                });
            }
        });
    }
}
