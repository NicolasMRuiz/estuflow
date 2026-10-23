<?php

namespace App\Feeds;

use Carbon\CarbonImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

class BoardParser
{
    public const SOURCE = 'https://alumnos.ibeltran.com.ar/';

    /** @return array<int, array<string, mixed>> */
    public function parse(string $html): array
    {
        $doc = new DOMDocument;
        $old = libxml_use_internal_errors(true);
        try {
            $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        $xp = new DOMXPath($doc);
        $cards = $xp->query('//div[contains(concat(" ",normalize-space(@class)," ")," noticia ")]');
        if (! $cards || $cards->length === 0 || $cards->length > 500) {
            throw new RuntimeException('board_structure');
        }
        $result = [];
        foreach ($cards as $card) {
            $title = $this->text(($this->first($xp, './/h3', $card)->textContent ?? ''));
            $date = trim(($this->first($xp, './/span[contains(concat(" ",normalize-space(@class)," ")," fecha ")]', $card)->textContent ?? ''));
            $image = $this->first($xp, './img', $card);
            $asset = $image instanceof DOMElement ? $this->asset($image->getAttribute('src')) : null;
            if (! $title || mb_strlen($title) > 250 || ! $asset || ! preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                throw new RuntimeException('board_contract');
            }
            try {
                $published = CarbonImmutable::createFromFormat('!d-m-Y', $date);
            } catch (\Throwable) {
                throw new RuntimeException('board_date');
            }
            if ($published->format('d-m-Y') !== $date) {
                throw new RuntimeException('board_date');
            }
            $description = $this->first($xp, './/div[contains(concat(" ",normalize-space(@class)," ")," descripcion ")]', $card);
            if (! $description) {
                throw new RuntimeException('board_description');
            }
            foreach ($xp->query('.//script|.//style|.//iframe', $description) ?: [] as $unsafe) {
                $unsafe->parentNode?->removeChild($unsafe);
            }
            $excerpt = mb_substr($this->text($description->textContent), 0, 1000);
            $attachments = [];
            foreach ($xp->query('.//a[@href]', $card) ?: [] as $anchor) {
                if ($anchor instanceof DOMElement && ($url = $this->asset($anchor->getAttribute('href')))) {
                    $attachments[] = $url;
                }
            }
            $attachments = array_values(array_unique($attachments));
            sort($attachments);
            // Observed cards have no permalink/ID; dated image filenames are stable,
            // unlike their repeated titles, pin order, CSS colour or edited descriptions.
            $key = hash('sha256', $published->toDateString().'|'.$asset);
            if (isset($result[$key])) {
                throw new RuntimeException('board_identity_collision');
            }
            $data = ['title' => $title, 'excerpt' => $excerpt, 'published_on' => $published->toDateString(), 'attachments' => $attachments];
            $result[$key] = ['source_key' => $key, 'content_hash' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))] + $data;
        }

        return array_values($result);
    }

    private function first(DOMXPath $xp, string $query, \DOMNode $context): ?\DOMNode
    {
        $nodes = $xp->query($query, $context);

        return $nodes === false ? null : $nodes->item(0);
    }

    private function text(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function asset(string $value): ?string
    {
        $value = str_starts_with($value, self::SOURCE) ? substr($value, strlen(self::SOURCE)) : $value;

        // Link only public source attachments, never imported private/signed links.
        return preg_match('~^files-cartelera/[a-zA-Z0-9_-]+\.(?:pdf|jpe?g|png|webp|docx?|xlsx?)$~i', $value) ? self::SOURCE.$value : null;
    }
}
