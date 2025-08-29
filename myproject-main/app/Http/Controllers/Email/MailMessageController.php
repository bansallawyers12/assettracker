<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\MailAttachment;
use App\Models\MailLabel;
use App\Models\MailMessage;
use App\Models\BusinessEntity;
use App\Models\Asset;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MailMessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = MailMessage::query()->where('user_id', $userId);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%$search%")
                    ->orWhere('sender_email', 'like', "%$search%")
                    ->orWhere('sender_name', 'like', "%$search%")
                    ->orWhere('text_content', 'like', "%$search%")
                    ->orWhere('recipients', 'like', "%$search%");
            });
        }

        if ($labelId = $request->integer('label_id')) {
            $query->whereHas('labels', function ($q) use ($labelId) {
                $q->where('mail_labels.id', $labelId);
            });
        }

        if ($sender = $request->string('sender')->toString()) {
            $query->where('sender_email', 'like', "%$sender%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sent_date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sent_date', '<=', $request->date('date_to'));
        }

        $messages = $query->latest('sent_date')->paginate(20)->withQueryString();

        $labels = MailLabel::where('user_id', $userId)->orderBy('type')->orderBy('name')->get();

        return view('emails.index', [
            'messages' => $messages,
            'labels' => $labels,
            'filters' => $request->only(['search', 'label_id', 'sender', 'date_from', 'date_to']),
        ]);
    }

    public function show(int $id)
    {
        $userId = Auth::id();
        $message = MailMessage::with(['attachments', 'labels'])
            ->where('user_id', $userId)
            ->findOrFail($id);

        return view('emails.show', [
            'message' => $message,
        ]);
    }

    public function allocateToBusinessEntity(Request $request, int $id)
    {
        $request->validate([
            'business_entity_id' => 'required|exists:business_entities,id',
        ]);

        $userId = Auth::id();
        $message = MailMessage::where('user_id', $userId)->findOrFail($id);
        $entity = BusinessEntity::where('user_id', $userId)->findOrFail($request->integer('business_entity_id'));

        // Attach to entity pivot
        $message->businessEntities()->syncWithoutDetaching([$entity->id]);

        // Ensure a label exists for this entity and attach it to the message
        $labelName = 'Entity: ' . $entity->legal_name;
        $label = MailLabel::firstOrCreate([
            'user_id' => $userId,
            'name' => $labelName,
        ], [
            'type' => 'entity',
            'color' => '#fde68a',
        ]);
        $message->labels()->syncWithoutDetaching([$label->id]);

        // Save attachments as documents for the business entity
        $this->saveAttachmentsForEntity($message, $entity);

        return back()->with('status', 'Email allocated to business entity.');
    }

    public function allocateToAsset(Request $request, int $id)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
        ]);

        $userId = Auth::id();
        $message = MailMessage::where('user_id', $userId)->findOrFail($id);

        $asset = Asset::where('user_id', $userId)->findOrFail($request->integer('asset_id'));

        // Attach to asset pivot
        $message->assets()->syncWithoutDetaching([$asset->id]);

        // Ensure a label exists for this asset and attach it to the message
        $labelName = 'Asset: ' . $asset->name;
        $label = MailLabel::firstOrCreate([
            'user_id' => $userId,
            'name' => $labelName,
        ], [
            'type' => 'asset',
            'color' => '#bbf7d0',
        ]);
        $message->labels()->syncWithoutDetaching([$label->id]);

        // Save attachments as documents for the asset (and its parent entity)
        $this->saveAttachmentsForAsset($message, $asset);

        return back()->with('status', 'Email allocated to asset.');
    }

    private function saveAttachmentsForEntity(MailMessage $message, BusinessEntity $entity): void
    {
        try {
            $sanitizedEntity = $this->sanitizeFilename((string) $entity->legal_name);
            $docsPath = "BusinessEntities/{$entity->id}_{$sanitizedEntity}/docs";
            if (!Storage::disk('s3')->exists($docsPath)) {
                Storage::disk('s3')->makeDirectory($docsPath);
            }

            foreach ($message->attachments as $att) {
                if (!$att->storage_path || !Storage::exists($att->storage_path)) {
                    continue;
                }

                $filename = $att->filename ?: ('attachment_' . $att->id);
                $targetPath = $docsPath . '/' . $filename;

                if (!Storage::disk('s3')->exists($targetPath)) {
                    $binary = Storage::get($att->storage_path);
                    Storage::disk('s3')->put($targetPath, $binary);
                }

                if (!Document::where('path', $targetPath)->exists()) {
                    Document::create([
                        'business_entity_id' => $entity->id,
                        'asset_id' => null,
                        'file_name' => $filename,
                        'path' => $targetPath,
                        'type' => 'other',
                        'description' => 'Imported from email #' . $message->id . ': ' . (string) $message->subject,
                        'filetype' => $att->content_type ?: 'application/octet-stream',
                        'user_id' => Auth::id(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to save email attachments as documents (entity)', [
                'error' => $e->getMessage(),
                'mail_message_id' => $message->id,
                'business_entity_id' => $entity->id,
            ]);
        }
    }

    private function saveAttachmentsForAsset(MailMessage $message, Asset $asset): void
    {
        try {
            $entity = $asset->businessEntity;
            if (!$entity) return;
            $sanitizedEntity = $this->sanitizeFilename((string) $entity->legal_name);
            $assetFolderName = $asset->id . '_' . $this->sanitizeFilename((string) $asset->name);
            $docsPath = "BusinessEntities/{$entity->id}_{$sanitizedEntity}/docs/{$assetFolderName}";
            if (!Storage::disk('s3')->exists($docsPath)) {
                Storage::disk('s3')->makeDirectory($docsPath);
            }

            foreach ($message->attachments as $att) {
                if (!$att->storage_path || !Storage::exists($att->storage_path)) {
                    continue;
                }

                $filename = $att->filename ?: ('attachment_' . $att->id);
                $targetPath = $docsPath . '/' . $filename;

                if (!Storage::disk('s3')->exists($targetPath)) {
                    $binary = Storage::get($att->storage_path);
                    Storage::disk('s3')->put($targetPath, $binary);
                }

                if (!Document::where('path', $targetPath)->exists()) {
                    Document::create([
                        'business_entity_id' => $entity->id,
                        'asset_id' => $asset->id,
                        'file_name' => $filename,
                        'path' => $targetPath,
                        'type' => 'other',
                        'description' => 'Imported from email #' . $message->id . ': ' . (string) $message->subject,
                        'filetype' => $att->content_type ?: 'application/octet-stream',
                        'user_id' => Auth::id(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to save email attachments as documents (asset)', [
                'error' => $e->getMessage(),
                'mail_message_id' => $message->id,
                'asset_id' => $asset->id,
            ]);
        }
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $name) ?? '';
        return trim(str_replace(' ', '-', $name));
    }
}


