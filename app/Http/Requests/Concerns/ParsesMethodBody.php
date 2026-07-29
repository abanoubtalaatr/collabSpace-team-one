<?php

namespace App\Http\Requests\Concerns;

trait ParsesMethodBody
{
    /**
     * PHP does not populate $_POST for multipart PUT/PATCH/DELETE.
     * Recover JSON, urlencoded, multipart, and user_ids[n] style payloads.
     *
     * @param  list<string>  $keys
     */
    protected function mergeBodyParameters(array $keys = []): void
    {
        $payload = [];

        $contentTypeHeader = (string) $this->header('Content-Type', '');
        $contentType = strtolower($contentTypeHeader);
        $raw = (string) $this->getContent();

        if ($raw !== '' && (str_contains($contentType, 'application/json') || str_starts_with(ltrim($raw), '{') || str_starts_with(ltrim($raw), '['))) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        } elseif ($raw !== '' && str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($raw, $parsed);
            if (is_array($parsed)) {
                $payload = $parsed;
            }
        } elseif ($raw !== '' && str_contains($contentType, 'multipart/form-data')) {
            $payload = $this->parseMultipartFormData($raw, $contentTypeHeader);
        }

        foreach ($this->query() as $key => $value) {
            if ($value !== null && $value !== '') {
                $payload[$key] = $value;
            }
        }

        $sources = array_merge($payload, $this->request->all(), $this->query->all());

        // Expand user_ids[0] / user_ids.0 into user_ids arrays (same shape as Add Members).
        $indexedUserIds = [];
        foreach ($sources as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (preg_match('/^user_ids(?:\[(\d+)\]|\.(\d+))$/', $key, $matches) === 1) {
                $index = (int) (($matches[1] ?? '') !== '' ? $matches[1] : ($matches[2] ?? 0));
                $indexedUserIds[$index] = $value;
            }
        }

        if ($indexedUserIds !== []) {
            ksort($indexedUserIds);
            $payload['user_ids'] = array_values($indexedUserIds);
        }

        // parse_str / JSON may already provide user_ids as an array.
        if (! isset($payload['user_ids']) && isset($sources['user_ids'])) {
            $payload['user_ids'] = $sources['user_ids'];
        }

        if ($keys !== []) {
            $payload = array_intersect_key($payload, array_flip($keys));
        }

        $toMerge = [];
        foreach ($payload as $key => $value) {
            $current = $this->input($key);
            if ($current === null || $current === '' || $current === []) {
                $toMerge[$key] = $value;
            }
        }

        if ($toMerge !== []) {
            $this->merge($toMerge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseMultipartFormData(string $raw, string $contentType): array
    {
        if (! preg_match('/boundary=(?:"([^"]+)"|([^;]+))/i', $contentType, $matches)) {
            return [];
        }

        $boundary = $matches[1] !== '' ? $matches[1] : trim($matches[2]);
        $parts = preg_split('/\R?--'.preg_quote($boundary, '/').'\R?/', $raw) ?: [];
        $data = [];

        foreach ($parts as $part) {
            $part = ltrim($part, "\r\n");

            if ($part === '' || $part === '--' || str_starts_with($part, '--')) {
                continue;
            }

            if (! preg_match('/Content-Disposition:\s*form-data;\s*name="([^"]+)"/i', $part, $nameMatch)) {
                continue;
            }

            $name = $nameMatch[1];

            // Skip file fields — only scalar form values matter here.
            if (preg_match('/filename="/i', $part)) {
                continue;
            }

            $value = '';
            if (preg_match('/\R\R(.*)$/s', $part, $valueMatch)) {
                $value = rtrim($valueMatch[1], "\r\n");
            }

            $data[$name] = $value;
        }

        return $data;
    }
}
