<?php

namespace App\Http\Requests\Concerns;

trait ParsesMethodBody
{
    /**
     * PHP does not populate $_POST for multipart PUT/PATCH/DELETE.
     * Recover JSON, urlencoded, and user_ids[n] style payloads from the raw body / query.
     *
     * @param  list<string>  $keys
     */
    protected function mergeBodyParameters(array $keys = []): void
    {
        $payload = [];

        $contentType = strtolower((string) $this->header('Content-Type', ''));
        $raw = trim((string) $this->getContent());

        if ($raw !== '' && (str_contains($contentType, 'application/json') || str_starts_with($raw, '{') || str_starts_with($raw, '['))) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        } elseif ($raw !== '' && str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($raw, $parsed);
            if (is_array($parsed)) {
                $payload = $parsed;
            }
        }

        foreach ($this->query() as $key => $value) {
            if ($value !== null && $value !== '') {
                $payload[$key] = $value;
            }
        }

        // Expand user_ids[0] / user_ids.0 into user_ids arrays (same shape as Add Members).
        $indexedUserIds = [];
        foreach (array_merge($payload, $this->request->all(), $this->query->all()) as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (preg_match('/^user_ids(?:\[(\d+)\]|\.(\d+))$/', $key, $matches) === 1) {
                $index = (int) (($matches[1] ?? '') !== '' ? $matches[1] : ($matches[2] ?? 0));
                $indexedUserIds[$index] = $value;
            }
        }

        if ($indexedUserIds !== [] && ! isset($payload['user_ids'])) {
            ksort($indexedUserIds);
            $payload['user_ids'] = array_values($indexedUserIds);
        }

        if ($keys !== []) {
            $payload = array_intersect_key($payload, array_flip($keys));
        }

        $toMerge = [];
        foreach ($payload as $key => $value) {
            if ($this->input($key) === null || $this->input($key) === '') {
                $toMerge[$key] = $value;
            }
        }

        if ($toMerge !== []) {
            $this->merge($toMerge);
        }
    }
}
