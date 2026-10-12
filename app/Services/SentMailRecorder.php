<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\MailLabel;
use App\Models\MailMessage;
use Illuminate\Support\Facades\Log;

class SentMailRecorder
{
    public function record(
        int $userId,
        string $senderName,
        string $subject,
        string $recipients,
        string $message,
        BusinessEntity $entity,
        ?int $assetId = null,
    ): void {
        try {
            $mailMessage = MailMessage::create([
                'user_id' => $userId,
                'subject' => $subject,
                'sender_email' => config('mail.from.address'),
                'sender_name' => $senderName,
                'recipients' => $recipients,
                'text_content' => $message,
                'html_content' => nl2br(e($message)),
                'sent_date' => now(),
                'status' => 'sent',
            ]);

            $mailMessage->businessEntities()->attach($entity->id);

            $entityLabel = MailLabel::firstOrCreate([
                'type' => 'entity',
                'name' => 'Entity: '.$entity->legal_name,
            ], [
                'user_id' => $userId,
                'color' => '#fde68a',
            ]);
            $mailMessage->labels()->attach($entityLabel->id);

            if ($assetId === null) {
                return;
            }

            $asset = Asset::query()
                ->where('business_entity_id', $entity->id)
                ->find($assetId);

            if ($asset === null) {
                return;
            }

            $mailMessage->assets()->attach($asset->id);

            $assetLabel = MailLabel::firstOrCreate([
                'type' => 'asset',
                'name' => 'Asset: '.$asset->name,
            ], [
                'user_id' => $userId,
                'color' => '#bbf7d0',
            ]);
            $mailMessage->labels()->attach($assetLabel->id);
        } catch (\Throwable $e) {
            Log::warning('Failed to store sent email', [
                'error' => $e->getMessage(),
                'business_entity_id' => $entity->id,
                'asset_id' => $assetId,
            ]);
        }
    }
}
