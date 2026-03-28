<?php

namespace App\Services;

use App\Models\MailAttachment;
use App\Models\MailLabel;
use App\Models\MailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MsgParserService
{
    public function parseAndUpdateMessage(MailMessage $message, string $storedPath): void
    {
        $absolutePath = Storage::path($storedPath);
        $data = $this->parseViaHttpService($absolutePath);

        if (!is_array($data)) {
            $data = $this->parseViaLocalScript($absolutePath);
        }

        if (!is_array($data)) {
            return;
        }

        $this->updateMessageFromParsedData($message, $data);
        $this->assignPrimaryLabel($message);
    }

    protected function parseViaHttpService(string $absolutePath): ?array
    {
        $url = (string) config('services.python_email.url', 'http://127.0.0.1:5001');
        $enabled = (bool) config('services.python_email.enabled', true);
        if (!$enabled) {
            return null;
        }

        try {
            if (!is_file($absolutePath)) {
                return null;
            }

            $response = Http::timeout((int) config('services.python_email.timeout', 30))
                ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
                ->post(rtrim($url, '/') . '/email/parse');

            if (!$response->successful()) {
                Log::warning('Python email service returned non-success status', [
                    'status' => $response->status(),
                    'url' => $url,
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);
                return null;
            }

            $data = $response->json();
            if (!is_array($data)) {
                Log::warning('Python email service returned invalid JSON payload');
                return null;
            }

            if (isset($data['success']) && $data['success'] === false) {
                Log::warning('Python email service parsing failed', [
                    'error' => $data['error'] ?? 'unknown',
                ]);
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            Log::info('Python email service not available, falling back to local parser', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function parseViaLocalScript(string $absolutePath): ?array
    {
        $python = $this->detectPython();
        // Support: python/ folder, storage/app/scripts, storage/app/private/scripts
        $scriptCandidates = [
            base_path('python/parse_msg_simple.py'),
            Storage::path('scripts/parse_msg_simple.py'),
            Storage::path('private/scripts/parse_msg_simple.py'),
        ];
        $scriptPath = null;
        foreach ($scriptCandidates as $candidate) {
            if (file_exists($candidate)) {
                $scriptPath = $candidate;
                break;
            }
        }

        if (!$scriptPath) {
            Log::error('Python parser script not found', ['candidates' => $scriptCandidates]);
            return null;
        }

        $command = sprintf('%s "%s" "%s" 2>&1', $python, str_replace('\\', '/', $scriptPath), str_replace('\\', '/', $absolutePath));
        Log::info('Running email parser', ['command' => $command]);

        $output = [];
        $exitCode = 0;
        @exec($command, $output, $exitCode);
        $joined = implode("\n", $output);

        $jsonStart = strpos($joined, '{');
        $jsonEnd = strrpos($joined, '}');
        if ($jsonStart === false || $jsonEnd === false) {
            Log::warning('No JSON found in parser output', ['output' => mb_substr($joined, 0, 1000)]);
            return null;
        }
        $json = substr($joined, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            Log::warning('Parser JSON decode failed', ['json' => mb_substr($json, 0, 1000)]);
            return null;
        }

        return $data;
    }

    protected function updateMessageFromParsedData(MailMessage $message, array $data): void
    {
        $message->update([
            'subject' => $this->sanitize($data['subject'] ?? $message->subject),
            'sender_name' => $this->sanitize($data['sender_name'] ?? $message->sender_name),
            'sender_email' => $this->sanitize($data['sender_email'] ?? $message->sender_email),
            'recipients' => isset($data['recipients']) ? json_encode($data['recipients']) : $message->recipients,
            'sent_date' => $data['sent_date'] ?? $message->sent_date,
            'html_content' => $this->sanitize($data['html_content'] ?? $message->html_content),
            'text_content' => $this->sanitize($data['text_content'] ?? $message->text_content),
            'status' => 'parsed',
        ]);

        foreach ((array)($data['attachments'] ?? []) as $attachment) {
            $filename = $attachment['filename'] ?? 'attachment';
            $contentBase64 = $attachment['content_base64'] ?? null;
            $contentType = $attachment['content_type'] ?? 'application/octet-stream';
            $isInline = (bool)($attachment['is_inline'] ?? false);

            if (!$contentBase64) {
                continue;
            }

            $binary = base64_decode($contentBase64, true);
            if ($binary === false) {
                continue;
            }

            $attachmentPath = 'emails/' . $message->id . '/attachments/' . $filename;
            Storage::put($attachmentPath, $binary);
            MailAttachment::create([
                'mail_message_id' => $message->id,
                'filename' => $filename,
                'content_type' => $contentType,
                'file_size' => strlen($binary),
                'storage_path' => $attachmentPath,
                'is_inline' => $isInline,
            ]);
        }
    }

    protected function detectPython(): string
    {
        foreach (['py -3', 'py', 'python3', 'python'] as $cmd) {
            $test = $cmd . ' --version';
            $exitCode = 0;
            @exec($test, $out, $exitCode);
            if ($exitCode === 0) {
                return $cmd;
            }
        }
        return 'python';
    }

    protected function sanitize($value)
    {
        if (is_string($value)) {
            if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
                return $value;
            }
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
            if ($converted === false) {
                $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            }
            return $converted !== false ? $converted : '';
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return $value;
    }

    protected function assignPrimaryLabel(MailMessage $message): void
    {
        $inbox = MailLabel::firstOrCreate([
            'user_id' => $message->user_id,
            'name' => 'Inbox',
        ], [
            'type' => 'system',
            'color' => '#2563eb',
        ]);
        $sent = MailLabel::firstOrCreate([
            'user_id' => $message->user_id,
            'name' => 'Sent',
        ], [
            'type' => 'system',
            'color' => '#10b981',
        ]);

        $sender = (string) ($message->sender_email ?? '');
        $isSent = str_contains($sender, '@yourdomain.com');
        $label = $isSent ? $sent : $inbox;

        if ($message->labels()->count() === 0) {
            $message->labels()->attach($label->id);
        }
    }
}


