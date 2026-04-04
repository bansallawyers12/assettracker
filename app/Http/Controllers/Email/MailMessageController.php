<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\MailAttachment;
use App\Models\MailLabel;
use App\Models\MailMessage;
use App\Models\BusinessEntity;
use App\Models\Asset;
use App\Models\Document;
use App\Services\DocumentUploadService;
use App\Services\MsgParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MailMessageController extends Controller
{
    public function __construct(
        private DocumentUploadService $documentUploadService
    ) {}

    public function index(Request $request)
    {
        $query = MailMessage::query();

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

        $labels = MailLabel::orderBy('type')->orderBy('name')->get();

        return view('emails.index', [
            'messages' => $messages,
            'labels' => $labels,
            'filters' => $request->only(['search', 'label_id', 'sender', 'date_from', 'date_to']),
        ]);
    }

    public function uploadIndex(Request $request)
    {
        $query = MailMessage::query();

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

        $type = strtolower($request->string('type')->toString());
        if ($type === 'inbox' || $type === 'sent') {
            $labelName = $type === 'sent' ? 'Sent' : 'Inbox';
            $query->whereHas('labels', function ($q) use ($labelName) {
                $q->where('mail_labels.name', $labelName);
            });
        }

        $messages = $query->latest('sent_date')->paginate(20)->withQueryString();
        $labels = MailLabel::orderBy('type')->orderBy('name')->get();

        return view('emails.upload', [
            'messages' => $messages,
            'labels' => $labels,
            'filters' => $request->only(['search', 'label_id', 'type']),
        ]);
    }

    public function uploadMsg(Request $request, MsgParserService $msgParserService)
    {
        $validated = $request->validate([
            'email_files' => ['required', 'array', 'max:10'],
            'email_files.*' => ['required', 'file', 'mimes:msg', 'max:30720'],
        ], [
            'email_files.required' => 'Please select at least one .msg file.',
            'email_files.*.mimes' => 'Only .msg files are allowed.',
            'email_files.max' => 'Maximum 10 files can be uploaded at once.',
        ]);

        $userId = Auth::id();
        $uploadedCount = 0;
        $skippedCount = 0;
        $reprocessedCount = 0;
        $failedCount = 0;

        foreach ($validated['email_files'] as $file) {
            try {
                $sourceHash = hash_file('sha256', $file->getRealPath()) ?: null;
                $existing = null;
                if ($sourceHash) {
                    $existing = MailMessage::where('source_hash', $sourceHash)->latest('id')->first();
                }

                if ($existing) {
                    $alreadyParsed = $existing->status === 'parsed'
                        && (filled($existing->html_content) || filled($existing->text_content) || filled($existing->sender_email));

                    if ($alreadyParsed) {
                        $skippedCount++;
                        continue;
                    }

                    if (blank($existing->source_path) || !Storage::exists($existing->source_path)) {
                        $existing->source_path = $file->store('emails/uploads/' . $userId);
                        $existing->save();
                    }

                    $msgParserService->parseAndUpdateMessage($existing, $existing->source_path);
                    $existing->refresh();
                    if ($existing->status === 'parsed') {
                        $reprocessedCount++;
                    } else {
                        $failedCount++;
                    }
                    continue;
                }

                $storedPath = $file->store('emails/uploads/' . $userId);
                $message = MailMessage::create([
                    'user_id' => $userId,
                    'subject' => $file->getClientOriginalName(),
                    'sender_name' => null,
                    'sender_email' => null,
                    'recipients' => null,
                    'sent_date' => now(),
                    'html_content' => null,
                    'text_content' => null,
                    'status' => 'uploaded',
                    'source_type' => 'upload',
                    'source_path' => $storedPath,
                    'source_hash' => $sourceHash,
                ]);

                $msgParserService->parseAndUpdateMessage($message, $storedPath);
                $uploadedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                Log::warning('MSG upload failed', [
                    'user_id' => $userId,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $statusParts = [];
        if ($uploadedCount > 0) {
            $statusParts[] = $uploadedCount . ' uploaded';
        }
        if ($skippedCount > 0) {
            $statusParts[] = $skippedCount . ' skipped (duplicate)';
        }
        if ($reprocessedCount > 0) {
            $statusParts[] = $reprocessedCount . ' reprocessed';
        }
        if ($failedCount > 0) {
            $statusParts[] = $failedCount . ' failed';
        }
        if ($statusParts === []) {
            $statusParts[] = 'No files were processed';
        }

        return redirect()->route('emails.upload')->with('status', 'Email upload completed: ' . implode(', ', $statusParts) . '.');
    }

    public function show(int $id)
    {
        $message = MailMessage::with(['attachments', 'labels'])
            ->findOrFail($id);

        return view('emails.show', [
            'message' => $message,
        ]);
    }

    public function reply(int $id)
    {
        $message = MailMessage::with(['attachments', 'labels'])
            ->findOrFail($id);

        return view('emails.reply', [
            'message' => $message,
        ]);
    }

    public function getReplyData(int $id)
    {
        $message = MailMessage::findOrFail($id);

        return response()->json([
            'subject' => 'Re: ' . ($message->subject ?: '(No subject)'),
            'to_email' => $message->sender_email,
            'sender_name' => $message->sender_name,
            'original_message' => $message->text_content ?: $message->html_content,
            'original_date' => $message->sent_date ? $message->sent_date->format('Y-m-d H:i') : '',
        ]);
    }

    public function allocateToBusinessEntity(Request $request, int $id)
    {
        $request->validate([
            'business_entity_id' => ['required', BusinessEntity::ruleExistsOperational()],
        ]);

        $message = MailMessage::findOrFail($id);
        $entity = BusinessEntity::findOrFail($request->integer('business_entity_id'));

        // Attach to entity pivot
        $message->businessEntities()->syncWithoutDetaching([$entity->id]);

        // Ensure a shared label exists for this entity and attach it to the message
        $labelName = 'Entity: ' . $entity->legal_name;
        $label = MailLabel::firstOrCreate([
            'type' => 'entity',
            'name' => $labelName,
        ], [
            'user_id' => Auth::id(),
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

        $message = MailMessage::findOrFail($id);
        $asset = Asset::findOrFail($request->integer('asset_id'));

        // Attach to asset pivot
        $message->assets()->syncWithoutDetaching([$asset->id]);

        // Ensure a shared label exists for this asset and attach it to the message
        $labelName = 'Asset: ' . $asset->name;
        $label = MailLabel::firstOrCreate([
            'type' => 'asset',
            'name' => $labelName,
        ], [
            'user_id' => Auth::id(),
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

            $category = $this->documentUploadService->firstOrCreateCategoryNamed(
                $entity,
                null,
                DocumentUploadService::IMPORTED_FROM_EMAIL_CATEGORY_TITLE
            );

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
                    $checklistLabel = pathinfo($filename, PATHINFO_FILENAME) ?: $filename;
                    $fileSize = Storage::disk('s3')->exists($targetPath)
                        ? Storage::disk('s3')->size($targetPath)
                        : null;

                    Document::create([
                        'business_entity_id' => $entity->id,
                        'asset_id' => null,
                        'document_category_id' => $category->id,
                        'checklist_label' => $checklistLabel,
                        'file_name' => $filename,
                        'path' => $targetPath,
                        'type' => 'other',
                        'description' => 'Imported from email #' . $message->id . ': ' . (string) $message->subject,
                        'filetype' => $att->content_type ?: 'application/octet-stream',
                        'file_size' => $fileSize,
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

            $category = $this->documentUploadService->firstOrCreateCategoryNamed(
                $entity,
                $asset,
                DocumentUploadService::IMPORTED_FROM_EMAIL_CATEGORY_TITLE
            );

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
                    $checklistLabel = pathinfo($filename, PATHINFO_FILENAME) ?: $filename;
                    $fileSize = Storage::disk('s3')->exists($targetPath)
                        ? Storage::disk('s3')->size($targetPath)
                        : null;

                    Document::create([
                        'business_entity_id' => $entity->id,
                        'asset_id' => $asset->id,
                        'document_category_id' => $category->id,
                        'checklist_label' => $checklistLabel,
                        'file_name' => $filename,
                        'path' => $targetPath,
                        'type' => 'other',
                        'description' => 'Imported from email #' . $message->id . ': ' . (string) $message->subject,
                        'filetype' => $att->content_type ?: 'application/octet-stream',
                        'file_size' => $fileSize,
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

    /**
     * Send a new email
     */
    public function sendEmail(Request $request)
    {
        $request->validate([
            'from_email' => 'required|email',
            'to_email' => 'required|email',
            'cc_email' => 'nullable|email',
            'bcc_email' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // Max 10MB per file
            'business_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
        ]);

        try {
            $userId = Auth::id();
            
            // Create the email instance
            $email = new \App\Mail\ContactEmail(
                $request->input('subject'),
                $request->input('message'),
                $request->file('attachments', []),
                $request->input('from_email')
            );
            
            // Send the email
            \Illuminate\Support\Facades\Mail::to($request->input('to_email'))
                ->cc($request->input('cc_email'))
                ->bcc($request->input('bcc_email'))
                ->send($email);

            // Log the sent email
            Log::info('Email sent successfully', [
                'from' => $request->input('from_email'),
                'to' => $request->input('to_email'),
                'subject' => $request->input('subject'),
                'user_id' => $userId,
                'business_entity_id' => $request->input('business_entity_id'),
            ]);

            // Store the sent email in the database if business entity is associated
            if ($request->input('business_entity_id')) {
                $this->storeSentEmail($request, $userId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save email as draft
     */
    public function saveDraft(Request $request)
    {
        $request->validate([
            'from_email' => 'required|email',
            'to_email' => 'required|email',
            'cc_email' => 'nullable|email',
            'bcc_email' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'business_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'template_id' => 'nullable|exists:email_templates,id',
        ]);

        try {
            $userId = Auth::id();
            
            // Store draft in database
            $draft = \App\Models\EmailDraft::create([
                'user_id' => $userId,
                'from_email' => $request->input('from_email'),
                'to_email' => $request->input('to_email'),
                'cc_email' => $request->input('cc_email'),
                'bcc_email' => $request->input('bcc_email'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'business_entity_id' => $request->input('business_entity_id'),
                'template_id' => $request->input('template_id'),
                'attachments' => [], // Attachments would need to be handled separately
            ]);

            Log::info('Email draft saved', [
                'draft_id' => $draft->id,
                'user_id' => $userId,
                'subject' => $request->input('subject'),
                'business_entity_id' => $request->input('business_entity_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft saved successfully!',
                'draft_id' => $draft->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save draft', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's email drafts
     */
    public function drafts()
    {
        $drafts = \App\Models\EmailDraft::query()
            ->with(['businessEntity', 'template'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'drafts' => $drafts->items(),
            'pagination' => [
                'current_page' => $drafts->currentPage(),
                'last_page' => $drafts->lastPage(),
                'per_page' => $drafts->perPage(),
                'total' => $drafts->total(),
            ]
        ]);
    }

    /**
     * Store sent email in database for business entity association
     */
    private function storeSentEmail(Request $request, int $userId)
    {
        try {
            // Create a mail message record
            $mailMessage = MailMessage::create([
                'user_id' => $userId,
                'subject' => $request->input('subject'),
                'sender_email' => $request->input('from_email'),
                'sender_name' => Auth::user()->name,
                'recipients' => $request->input('to_email'),
                'text_content' => $request->input('message'),
                'html_content' => nl2br($request->input('message')),
                'sent_date' => now(),
                'status' => 'sent',
            ]);

            // Associate with business entity if provided
            if ($request->input('business_entity_id')) {
                $mailMessage->businessEntities()->attach($request->input('business_entity_id'));
                
                // Create a label for this entity
                $entity = \App\Models\BusinessEntity::find($request->input('business_entity_id'));
                if ($entity) {
                    $labelName = 'Entity: ' . $entity->legal_name;
                    $label = MailLabel::firstOrCreate([
                        'type' => 'entity',
                        'name' => $labelName,
                    ], [
                        'user_id' => $userId,
                        'color' => '#fde68a',
                    ]);
                    $mailMessage->labels()->attach($label->id);
                }
            }

            // Store attachments if any
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('email-attachments', 'public');
                    
                    MailAttachment::create([
                        'mail_message_id' => $mailMessage->id,
                        'filename' => $attachment->getClientOriginalName(),
                        'storage_path' => $path,
                        'content_type' => $attachment->getMimeType(),
                        'file_size' => $attachment->getSize(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::warning('Failed to store sent email', [
                'error' => $e->getMessage(),
                'mail_data' => $request->all(),
            ]);
        }
    }
}


