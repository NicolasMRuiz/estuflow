<?php

namespace Tests\Feature;

use App\Feeds\BoardParser;
use App\Feeds\SharedFeeds;
use App\Models\BoardPost;
use App\Models\FeedSync;
use App\Models\Holiday;
use App\Models\InboxNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class M04FeedsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0));
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/m04/board.html'));
    }

    private function due(string $key): void
    {
        FeedSync::whereKey($key)->update(['next_attempt_at' => now()->subMinute()]);
    }

    public function test_holiday_full_validation_multiple_same_day_new_types_and_cadence_survive_cache(): void
    {
        Http::fake(['*/feriados/2026' => Http::response([['fecha' => '2026-04-02', 'tipo' => 'inamovible', 'nombre' => 'Uno'], ['fecha' => '2026-04-02', 'tipo' => 'nuevo_tipo', 'nombre' => 'Dos']])]);
        $s = app(SharedFeeds::class);
        $this->assertTrue($s->holidays(2026));
        $this->assertDatabaseCount('holidays', 2);
        $this->assertSame('nuevo_tipo', Holiday::where('name', 'Dos')->sole()->type);
        Cache::forget('m04-feed');
        $this->assertFalse($s->holidays(2026));
        Http::assertSentCount(1);
        $this->travelTo(now()->addDays(14));
        $this->assertFalse($s->holidays(2026));
        $this->travelTo(now()->addDay());
        $this->assertTrue($s->holidays(2026));
        $this->assertDatabaseCount('holidays', 2);
        Http::assertSentCount(2);
    }

    public function test_changed_holidays_replace_atomically_and_invalid_payloads_retain_last_valid_copy(): void
    {
        Http::fake(['*/feriados/2026' => Http::sequence()->push([['fecha' => '2026-01-01', 'tipo' => 'inamovible', 'nombre' => 'Antes']])->push([['fecha' => '2026-01-01', 'tipo' => 'inamovible', 'nombre' => 'Después']])->push([['fecha' => '2026-01-02', 'tipo' => 'x', 'nombre' => 'Válido'], ['fecha' => '2027-01-01', 'tipo' => 'x', 'nombre' => 'Año ajeno']])->push([])->push('<html>error</html>')->push([], 404)->push([['fecha' => '2026-02-30', 'tipo' => 'x', 'nombre' => 'Fecha imposible']])]);
        $s = app(SharedFeeds::class);
        $s->holidays(2026);
        $this->due('holidays:2026');
        $this->assertTrue($s->holidays(2026));
        $this->assertSame('Después', Holiday::sole()->name);
        for ($i = 0; $i < 5; $i++) {
            $this->due('holidays:2026');
            $this->assertFalse($s->holidays(2026));
            $this->assertSame('Después', Holiday::sole()->name);
            $this->assertNotNull(FeedSync::find('holidays:2026')->last_success_at);
        }
    }

    public function test_timeout_unpublished_year_and_overlap_have_bounded_durable_retry(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $s = app(SharedFeeds::class);
        $this->assertFalse($s->holidays(2027));
        $sync = FeedSync::find('holidays:2027');
        $this->assertNull($sync->last_success_at);
        $this->assertTrue($sync->next_attempt_at->gt(now()->addMinutes(60)));
        $this->assertFalse($s->holidays(2027));
        FeedSync::create(['key' => 'holidays:2026', 'leased_until' => now()->addMinute()]);
        $this->assertFalse($s->holidays(2026));
    }

    public function test_board_structure_has_stable_identity_repeated_titles_and_safe_attachments(): void
    {
        $parser = app(BoardParser::class);
        $rows = $parser->parse($this->fixture());
        $this->assertCount(2, $rows);
        $this->assertNotSame($rows[0]['source_key'], $rows[1]['source_key']);
        $this->assertStringNotContainsString('alert', $rows[0]['excerpt']);
        $this->assertCount(1, $rows[0]['attachments']);
        $modified = $parser->parse(str_replace('Primer aviso ficticio.', 'Texto corregido.', $this->fixture()));
        $this->assertSame($rows[0]['source_key'], $modified[0]['source_key']);
        $this->assertNotSame($rows[0]['content_hash'], $modified[0]['content_hash']);
    }

    public function test_board_baseline_new_post_snapshot_and_retried_fanout_are_deduplicated(): void
    {
        $eligible = User::factory()->create();
        $this->configureAcademic($eligible);
        $notReady = User::factory()->create();
        $unverified = User::factory()->unverified()->create();
        $this->configureAcademic($unverified);
        $newHtml = str_replace('</body>', '<div class="noticia"><img src="files-cartelera/20261024120000.jpg"><h3>IMPORTANTE</h3><div class="descripcion">Nuevo ficticio.</div><span class="fecha">24-10-2026</span></div></body>', $this->fixture());
        Http::fake([BoardParser::SOURCE => Http::sequence()->push($this->fixture())->push($newHtml)->push(str_replace('Nuevo ficticio.', 'Editado ficticio.', $newHtml))->push('<html>Login o mantenimiento</html>')]);
        $s = app(SharedFeeds::class);
        $this->assertTrue($s->board());
        $s->fanOut();
        $this->assertDatabaseCount('board_posts', 2);
        $this->assertDatabaseCount('inbox_notices', 0);
        $this->due('board');
        $this->assertTrue($s->board());
        $newUser = User::factory()->create();
        $this->configureAcademic($newUser);
        $this->configureAcademic($notReady);
        $s->fanOut();
        $s->fanOut();
        $this->assertDatabaseCount('inbox_notices', 1);
        $this->assertSame($eligible->id, InboxNotice::sole()->user_id);
        DB::table('board_recipients')->update(['delivered_at' => null]);
        $s->fanOut();
        $this->assertDatabaseCount('inbox_notices', 1);
        $this->due('board');
        $s->board();
        $s->fanOut();
        $this->assertDatabaseCount('board_posts', 3);
        $this->assertDatabaseCount('inbox_notices', 1);
        $this->assertSame('Editado ficticio.', BoardPost::latest('id')->first()->excerpt);
        $this->due('board');
        $this->assertFalse($s->board());
        $this->assertDatabaseCount('board_posts', 3);
        $this->assertNotNull(FeedSync::find('board')->last_error);
    }

    public function test_board_daily_eight_am_cadence_and_missed_run_recovery(): void
    {
        Http::fake([BoardParser::SOURCE => Http::response($this->fixture())]);
        $s = app(SharedFeeds::class);
        $s->board();
        $this->assertSame('2026-10-24 11:00', FeedSync::find('board')->next_attempt_at->format('Y-m-d H:i'));
        $this->assertFalse($s->board());
        $this->travelTo(now()->addDays(3));
        $this->assertTrue($s->board());
        $this->assertFalse($s->board());
        Http::assertSentCount(2);
    }

    public function test_board_reordering_does_not_change_identity_and_collision_fails(): void
    {
        $p = app(BoardParser::class);
        $a = $p->parse($this->fixture());
        $doc = new \DOMDocument;
        @$doc->loadHTML($this->fixture());
        $xp = new \DOMXPath($doc);
        $nodes = $xp->query('//div[contains(@class,"noticia")]');
        $nodes->item(0)->parentNode->appendChild($nodes->item(0));
        $b = $p->parse($doc->saveHTML());
        $this->assertSame(array_reverse(array_column($a, 'source_key')), array_column($b, 'source_key'));
        $this->expectException(\RuntimeException::class);
        $p->parse(str_replace('20261022120000.jpg', '20261023150308.jpg', str_replace('22-10-2026', '23-10-2026', $this->fixture())));
    }

    public function test_stale_external_result_cannot_overwrite_a_later_claim(): void
    {
        Http::fake(function () {
            FeedSync::whereKey('holidays:2026')->update(['lease_token' => (string) Str::uuid()]);

            return Http::response([['fecha' => '2026-01-01', 'tipo' => 'inamovible', 'nombre' => 'Respuesta vieja']]);
        });
        $this->assertFalse(app(SharedFeeds::class)->holidays(2026));
        $this->assertDatabaseCount('holidays', 0);
        $this->assertNull(FeedSync::find('holidays:2026')->last_success_at);
    }

    public function test_invalid_card_and_oversized_response_never_replace_a_valid_baseline(): void
    {
        Http::fake([BoardParser::SOURCE => Http::sequence()->push($this->fixture())->push(str_replace('</body>', '<div class="noticia"><h3>Incompleta</h3></div></body>', $this->fixture()))]);
        $feeds = app(SharedFeeds::class);
        $this->assertTrue($feeds->board());
        $this->due('board');
        $this->assertFalse($feeds->board());
        $this->assertDatabaseCount('board_posts', 2);
        Http::fake(['*/feriados/2026' => Http::response(str_repeat('x', 2_000_001))]);
        $this->assertFalse($feeds->holidays(2026));
        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_early_baseline_does_not_fetch_twice_on_same_local_day(): void
    {
        $this->travelTo(now()->setTime(9, 0)); // 06:00 Buenos Aires
        Http::fake([BoardParser::SOURCE => Http::response($this->fixture())]);
        $feeds = app(SharedFeeds::class);
        $this->assertTrue($feeds->board());
        $this->travelTo(now()->setTime(12, 0));
        $this->assertFalse($feeds->board());
        $this->assertSame('2026-10-24 11:00', FeedSync::find('board')->next_attempt_at->format('Y-m-d H:i'));
        Http::assertSentCount(1);
    }
}
