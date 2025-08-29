<?php

namespace App\Jobs;

use App\Models\EmailDraft;
use App\Mail\ContactEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendScheduledEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get all drafts that are ready to be sent
            $readyDrafts = EmailDraft::readyToSend()->with(['user', 'businessEntity'])->get();

            foreach ($readyDrafts as $draft) {
                try {
                    $this->sendEmailFromDraft($draft);
                    
                    // Delete the draft after successful sending
                    $draft->delete();
                    
                    Log::info('Scheduled email sent successfully', [
                        'draft_id' => $draft->id,
                        'to' => $draft->to_email,
                        'subject' => $draft->subject,
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to send scheduled email', [
                        'draft_id' => $draft->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Update the draft to retry later
                    $draft->update([
                        'scheduled_at' => now()->addMinutes(15), // Retry in 15 minutes
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('SendScheduledEmails job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send email from a draft.
     */
    private function sendEmailFromDraft(EmailDraft $draft): void
    {
        // Create the email instance
        $email = new ContactEmail(
            $draft->subject,
            $draft->message,
            [], // Attachments would need to be handled separately
            $draft->from_email
        );
        
        // Send the email
        Mail::to($draft->to_email)
            ->cc($draft->cc_email)
            ->bcc($draft->bcc_email)
            ->send($email);
    }
}
