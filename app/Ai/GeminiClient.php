<?php

namespace App\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Talks to Google's Gemini API (generateContent). One call in, text (or JSON) and the token counts out.
 * Failures come back as an AiFailure with a message a school can read.
 */
class GeminiClient
{
    public const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * @param  array|null  $schema  When given, Gemini must answer with JSON in this shape (OpenAPI-style, types in capitals)
     * @param  array<int, array{mime: string, data: string}>  $files  Documents sent with the prompt (raw bytes), such as a PDF
     * @return array{text: string, input_tokens: int, output_tokens: int, finish: ?string}
     */
    public function generate(string $key, string $model, string $system, string $prompt, ?array $schema = null, int $maxTokens = 8192, array $files = []): array
    {
        $parts = array_map(fn ($f) => ['inlineData' => ['mimeType' => $f['mime'], 'data' => base64_encode($f['data'])]], $files);
        $parts[] = ['text' => $prompt];

        $config = ['maxOutputTokens' => $maxTokens, 'temperature' => 0.7];
        if ($schema) {
            $config += ['responseMimeType' => 'application/json', 'responseSchema' => $schema];
        }

        try {
            $response = Http::timeout(90)->connectTimeout(10)->acceptJson()
                ->withHeaders(['x-goog-api-key' => $key])
                ->post(self::ENDPOINT.'/'.rawurlencode($model).':generateContent', [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [['role' => 'user', 'parts' => $parts]],
                    'generationConfig' => $config,
                ]);
        } catch (ConnectionException) {
            throw new AiFailure('The AI service could not be reached. Check the internet connection and try again.');
        }

        $usage = $response->json('usageMetadata') ?? [];
        $tokens = [
            'input_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            // Google bills thinking as output
            'output_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0),
        ];

        if ($response->failed()) {
            $detail = (string) $response->json('error.message', '');
            throw new AiFailure(match (true) {
                in_array($response->status(), [400, 401, 403], true) && str_contains(strtolower($detail), 'api key') => 'The AI key was refused by Google. The platform owner needs to check it.',
                $response->status() === 404 => "Google does not offer the model \"{$model}\" to this key. The platform owner needs to choose another model.",
                $response->status() === 429 => 'The AI is busy or the platform has used up its Google allowance. Try again in a few minutes.',
                $response->status() >= 500 => 'The AI service had a problem on its side. Try again in a few minutes.',
                default => 'The AI could not answer this request'.($detail !== '' ? ': '.mb_strimwidth($detail, 0, 160, '…') : '.'),
            }, $tokens);
        }

        $candidate = $response->json('candidates.0') ?? [];
        $text = collect($candidate['content']['parts'] ?? [])->reject(fn ($p) => $p['thought'] ?? false)->pluck('text')->implode('');
        $finish = $candidate['finishReason'] ?? null;
        if (trim($text) === '') {
            throw new AiFailure($finish === 'SAFETY' || $response->json('promptFeedback.blockReason')
                ? 'The AI declined to answer this request.'
                : 'The AI gave an empty answer. Try again.', $tokens);
        }

        return ['text' => $text, 'finish' => $finish] + $tokens;
    }
    /**
     * One picture from an image model. Returns the picture's raw base64 and type, with the token counts Google reports.
     * Failures (including a refusal) come back as an AiFailure with a message a school can read.
     *
     * @return array{mime: string, data: string, input_tokens: int, output_tokens: int}
     */
    public function generateImage(string $key, string $model, string $prompt): array
    {
        try {
            $response = Http::timeout(120)->connectTimeout(10)->acceptJson()
                ->withHeaders(['x-goog-api-key' => $key])
                ->post(self::ENDPOINT.'/'.rawurlencode($model).':generateContent', [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['responseModalities' => ['IMAGE']],
                ]);
        } catch (ConnectionException) {
            throw new AiFailure('The picture service could not be reached. Check the internet connection and try again.');
        }

        $usage = $response->json('usageMetadata') ?? [];
        $tokens = ['input_tokens' => (int) ($usage['promptTokenCount'] ?? 0), 'output_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0)];

        if ($response->failed()) {
            $detail = (string) $response->json('error.message', '');
            throw new AiFailure(match (true) {
                in_array($response->status(), [400, 401, 403], true) && str_contains(strtolower($detail), 'api key') => 'The AI key was refused by Google. The platform owner needs to check it.',
                $response->status() === 404 => "Google does not offer the picture model \"{$model}\" to this key. The platform owner needs to choose another.",
                $response->status() === 429 => 'The picture service is busy or the platform has used up its Google allowance. Try again in a few minutes.',
                $response->status() >= 500 => 'The picture service had a problem on its side. Try again in a few minutes.',
                default => 'The picture could not be made'.($detail !== '' ? ': '.mb_strimwidth($detail, 0, 160, '…') : '.'),
            }, $tokens);
        }

        foreach ((array) $response->json('candidates.0.content.parts', []) as $part) {
            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if ($inline && ! empty($inline['data'])) {
                return ['mime' => $inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png', 'data' => $inline['data']] + $tokens;
            }
        }

        throw new AiFailure($response->json('candidates.0.finishReason') === 'SAFETY' || $response->json('promptFeedback.blockReason')
            ? 'The AI declined to make this picture. Describe it differently and try again.'
            : 'The AI did not make a picture. Try describing it differently.', $tokens);
    }
}
