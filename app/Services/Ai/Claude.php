<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin client for the Anthropic Messages API. Returns validated JSON or null.
 * Null always means "use the rule-based fallback", so the platform keeps working
 * when no key is configured, the network is down or the model returns bad JSON.
 */
class Claude
{
    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function model(): string
    {
        return (string) config('services.anthropic.model', 'claude-haiku-5-5');
    }

    public function label(): string
    {
        return $this->enabled() ? 'Claude (' . $this->model() . ')' : 'Rules fallback (no AI key set)';
    }

    /** Ask for a JSON answer. Cached by prompt so repeated views do not cost tokens. */
    public function json(string $system, string $prompt, int $maxTokens = 1500): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $key = 'claude:' . $this->model() . ':' . sha1($system . '|' . $prompt);
        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $res = Http::withHeaders([
                'x-api-key' => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
            ])
                ->acceptJson()
                ->timeout((int) config('services.anthropic.timeout', 25))
                ->post(rtrim((string) config('services.anthropic.url', 'https://api.anthropic.com'), '/') . '/v1/messages', [
                    'model' => $this->model(),
                    'max_tokens' => $maxTokens,
                    'temperature' => 0,
                    'system' => $system . "\n\nRespond with a single valid JSON value only. No prose, no markdown fences.",
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

            if (! $res->successful()) {
                Log::warning('Claude request failed', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 500)]);

                return null;
            }

            $text = collect($res->json('content', []))->where('type', 'text')->pluck('text')->implode('');
            $data = self::decode($text);
            if ($data === null) {
                Log::warning('Claude returned non-JSON', ['text' => mb_substr($text, 0, 300)]);

                return null;
            }

            Cache::put($key, $data, now()->addDay());

            return $data;
        } catch (Throwable $e) {
            Log::warning('Claude unavailable: ' . $e->getMessage());

            return null;
        }
    }

    /** Pull the first JSON object or array out of a model reply. */
    public static function decode(string $text): ?array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
        $start = strcspn($text, '{[');
        if ($start >= strlen($text)) {
            return null;
        }
        $open = $text[$start];
        $end = strrpos($text, $open === '{' ? '}' : ']');
        if ($end === false || $end < $start) {
            return null;
        }
        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($data) ? $data : null;
    }
}
