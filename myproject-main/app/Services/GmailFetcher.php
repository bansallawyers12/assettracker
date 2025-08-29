<?php

namespace App\Services;

use App\Models\MailLabel;
use App\Models\MailMessage;
use App\Models\MailAttachment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class GmailFetcher
{
    public function fetchAndStoreLatest(int $userId, int $limit = 10): int
    {
        $this->ensureSystemLabels($userId);

        $clientId = (string) config('gmail.client_id');
        $clientSecret = (string) config('gmail.client_secret');
        $refreshToken = (string) config('gmail.refresh_token');
        $userEmail = (string) config('gmail.user_email');
        $enabled = (bool) config('gmail.enabled');

        if (!$enabled || !$clientId || !$clientSecret || !$refreshToken || !$userEmail) {
            Log::warning('GmailFetcher: credentials not configured or disabled');
            return 0;
        }

        $http = new Client([
            'timeout' => 20,
        ]);

        // 1) Exchange refresh token for access token
        $accessToken = $this->getAccessToken($http, $clientId, $clientSecret, $refreshToken);
        if (!$accessToken) {
            Log::error('GmailFetcher: failed to get access token');
            return 0;
        }

        // 2) List messages
        $labelId = config('gmail.label', 'INBOX');
        $listUrl = 'https://gmail.googleapis.com/gmail/v1/users/me/messages';
        $query = [
            'maxResults' => $limit,
            'labelIds' => $labelId,
        ];
        $resp = $http->get($listUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => $query,
        ]);
        $data = json_decode((string) $resp->getBody(), true) ?: [];
        $messages = (array) ($data['messages'] ?? []);

        $fetched = 0;
        foreach ($messages as $m) {
            $gmailId = (string) ($m['id'] ?? '');
            if ($gmailId === '') continue;
            if (MailMessage::where('user_id', $userId)->where('gmail_id', $gmailId)->exists()) {
                continue;
            }

            $detail = $this->getMessage($http, $accessToken, $gmailId);
            if (!$detail) continue;

            [$subject, $fromName, $fromEmail, $sentAt, $recipients] = $this->parseHeaders($detail['payload']['headers'] ?? []);
            [$htmlBody, $textBody, $attachmentsMeta] = $this->extractBodiesAndAttachments($detail['payload'] ?? []);

            $model = MailMessage::create([
                'user_id' => $userId,
                'gmail_id' => $gmailId,
                'message_id' => (string) ($detail['id'] ?? ''),
                'subject' => $subject ?: '(No subject) ' . $gmailId,
                'sender_name' => $fromName,
                'sender_email' => $fromEmail,
                'recipients' => json_encode($recipients),
                'sent_date' => $sentAt ?: Carbon::now(),
                'html_content' => $htmlBody,
                'text_content' => $textBody,
                'status' => 'synced',
            ]);

            $labelName = str_contains((string) $model->sender_email, '@yourdomain.com') ? 'Sent' : 'Inbox';
            $label = MailLabel::where('user_id', $userId)->where('name', $labelName)->first();
            if ($label) {
                $model->labels()->attach($label->id);
            }

            // Download attachments
            foreach ($attachmentsMeta as $att) {
                $attachmentId = $att['attachmentId'] ?? null;
                $filename = $att['filename'] ?? 'attachment';
                if (!$attachmentId) continue;
                $binary = $this->getAttachment($http, $accessToken, $gmailId, $attachmentId);
                if ($binary === null) continue;

                $attachmentPath = 'emails/' . $model->id . '/attachments/' . $filename;
                Storage::put($attachmentPath, $binary);
                MailAttachment::create([
                    'mail_message_id' => $model->id,
                    'filename' => $filename,
                    'content_type' => $att['mimeType'] ?? 'application/octet-stream',
                    'file_size' => strlen($binary),
                    'storage_path' => $attachmentPath,
                    'is_inline' => false,
                ]);
            }

            $fetched++;
        }

        return $fetched;
    }

    protected function ensureSystemLabels(int $userId): void
    {
        foreach (['Inbox', 'Sent'] as $name) {
            MailLabel::firstOrCreate([
                'user_id' => $userId,
                'name' => $name,
            ], [
                'color' => $name === 'Inbox' ? '#2563eb' : '#10b981',
                'type' => 'system',
            ]);
        }
    }

    protected function getAccessToken(Client $http, string $clientId, string $clientSecret, string $refreshToken): ?string
    {
        try {
            $resp = $http->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);
            $json = json_decode((string) $resp->getBody(), true);
            return $json['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('GmailFetcher: token exchange failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function getMessage(Client $http, string $accessToken, string $id): ?array
    {
        try {
            $resp = $http->get('https://gmail.googleapis.com/gmail/v1/users/me/messages/' . $id, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'format' => 'full',
                ],
            ]);
            return json_decode((string) $resp->getBody(), true);
        } catch (\Throwable $e) {
            Log::warning('GmailFetcher: getMessage failed', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function getAttachment(Client $http, string $accessToken, string $messageId, string $attachmentId): ?string
    {
        try {
            $url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/' . $messageId . '/attachments/' . $attachmentId;
            $resp = $http->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);
            $json = json_decode((string) $resp->getBody(), true);
            $data = $json['data'] ?? null; // base64url
            if (!$data) return null;
            return $this->base64urlDecode($data);
        } catch (\Throwable $e) {
            Log::warning('GmailFetcher: getAttachment failed', ['message_id' => $messageId, 'attachment_id' => $attachmentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function parseHeaders(array $headers): array
    {
        $map = [];
        foreach ($headers as $h) {
            $name = strtolower($h['name'] ?? '');
            $value = (string) ($h['value'] ?? '');
            if ($name) $map[$name] = $value;
        }
        $subject = $map['subject'] ?? '';
        $from = $map['from'] ?? '';
        $date = $map['date'] ?? '';
        $to = $map['to'] ?? '';
        $cc = $map['cc'] ?? '';
        $bcc = $map['bcc'] ?? '';

        [$fromName, $fromEmail] = $this->splitAddress($from);
        $sentAt = null;
        try { $sentAt = Carbon::parse($date); } catch (\Throwable $e) { $sentAt = null; }

        $recipients = [
            'to' => $this->splitAddresses($to),
            'cc' => $this->splitAddresses($cc),
            'bcc' => $this->splitAddresses($bcc),
        ];

        return [$subject, $fromName, $fromEmail, $sentAt, $recipients];
    }

    protected function extractBodiesAndAttachments(array $payload): array
    {
        $html = '';
        $text = '';
        $attachments = [];

        $mimeType = $payload['mimeType'] ?? '';
        $body = $payload['body'] ?? [];
        if (!empty($body['data'])) {
            $decoded = $this->base64urlDecode($body['data']);
            if ($mimeType === 'text/html') {
                $html = $decoded;
            } else {
                $text = $decoded;
            }
        }

        foreach ((array) ($payload['parts'] ?? []) as $part) {
            $pMime = $part['mimeType'] ?? '';
            $pBody = $part['body'] ?? [];
            $fileName = (string) ($part['filename'] ?? '');
            $attachmentId = $pBody['attachmentId'] ?? null;

            if (!$fileName) {
                if (!empty($pBody['data'])) {
                    $decoded = $this->base64urlDecode($pBody['data']);
                    if ($pMime === 'text/html') $html = $decoded;
                    elseif ($pMime === 'text/plain') $text = $decoded;
                }
            } else {
                $attachments[] = [
                    'filename' => $fileName,
                    'attachmentId' => $attachmentId,
                    'mimeType' => $pMime,
                ];
            }

            // Nested parts
            if (!empty($part['parts'])) {
                [$h2, $t2, $a2] = $this->extractBodiesAndAttachments($part);
                if ($h2) $html = $h2;
                if ($t2) $text = $t2;
                $attachments = array_merge($attachments, $a2);
            }
        }

        return [$html, $text, $attachments];
    }

    protected function splitAddress(string $addr): array
    {
        $addr = trim($addr);
        if ($addr === '') return ['', ''];
        if (preg_match('/^"?(.*?)"?\s*<([^>]+)>$/', $addr, $m)) {
            return [trim($m[1]), trim($m[2])];
        }
        return ['', $addr];
    }

    protected function splitAddresses(string $line): array
    {
        $out = [];
        foreach (explode(',', $line) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            [, $email] = $this->splitAddress($part);
            if ($email) $out[] = $email;
        }
        return $out;
    }

    protected function base64urlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($data) ?: '';
    }
}


