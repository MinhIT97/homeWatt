<?php

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceTranscriber
{
    /**
     * Transcribe audio content to text using OpenAI Whisper API.
     */
    public function transcribe(string $audioContent, string $language = 'vi'): string
    {
        $apiKey = config('ai.providers.openai.api_key');

        if (empty($apiKey)) {
            Log::warning('OpenAI API key not configured for voice transcription');

            return '';
        }

        $tempFile = '';

        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'voice_').'.ogg';
            file_put_contents($tempFile, $audioContent);

            $response = Http::timeout(30)
                ->withHeader('Authorization', 'Bearer '.$apiKey)
                ->attach('file', file_get_contents($tempFile), 'voice.ogg')
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => $language,
                ]);

            if ($response->failed()) {
                Log::error('Whisper transcription failed', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                return '';
            }

            return $response->json('text', '');
        } catch (\Throwable $e) {
            Log::error('Voice transcription error', [
                'error' => $e->getMessage(),
            ]);

            return '';
        } finally {
            if ($tempFile !== '' && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Transcribe from a file path instead of raw content.
     */
    public function transcribeFile(string $filePath, string $language = 'vi'): string
    {
        if (! file_exists($filePath)) {
            Log::warning('Voice file not found', ['path' => $filePath]);

            return '';
        }

        $content = file_get_contents($filePath);

        return $this->transcribe($content, $language);
    }
}
