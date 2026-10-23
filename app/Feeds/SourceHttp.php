<?php

namespace App\Feeds;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SourceHttp
{
    public function get(string $url): string
    {
        $limit = (int) config('m04.http_max_bytes');
        $response = Http::timeout((int) config('m04.http_timeout'))->connectTimeout(3)->withOptions([
            'allow_redirects' => false,
            'progress' => static function ($total, $downloaded) use ($limit): void {
                if ($total > $limit || $downloaded > $limit) {
                    throw new RuntimeException('response_too_large');
                }
            },
        ])->withHeaders(['User-Agent' => 'EstuFlow/1.0 (shared public notice reader)'])->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('http_'.$response->status());
        }
        $body = $response->body();
        if (strlen($body) > $limit) {
            throw new RuntimeException('response_too_large');
        }

        return $body;
    }
}
