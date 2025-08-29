<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = EmailTemplate::forUser(Auth::id())
            ->orderBy('name')
            ->paginate(10);

        return view('email-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('email-templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $template = EmailTemplate::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('email-templates.index')
            ->with('success', 'Email template created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailTemplate $emailTemplate)
    {
        $this->authorize('view', $emailTemplate);
        
        return view('email-templates.show', compact('emailTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        $this->authorize('update', $emailTemplate);
        
        return view('email-templates.edit', compact('emailTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $this->authorize('update', $emailTemplate);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $emailTemplate->update($validated);

        return redirect()->route('email-templates.index')
            ->with('success', 'Email template updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $this->authorize('delete', $emailTemplate);
        
        $emailTemplate->delete();

        return redirect()->route('email-templates.index')
            ->with('success', 'Email template deleted successfully!');
    }

    /**
     * Preview a template with sample data.
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        $this->authorize('view', $emailTemplate);
        
        $sampleData = [
            'recipient_name' => 'John Doe',
            'sender_name' => Auth::user()->name,
            'company_name' => 'Your Company',
            'current_date' => now()->format('Y-m-d'),
        ];

        $processedSubject = $this->processTemplate($emailTemplate->subject, $sampleData);
        $processedBody = $this->processTemplate($emailTemplate->description, $sampleData);

        return response()->json([
            'subject' => $processedSubject,
            'body' => $processedBody,
        ]);
    }

    /**
     * Get templates for the compose email form.
     */
    public function getTemplates()
    {
        $templates = EmailTemplate::forUser(Auth::id())
            ->select('id', 'name', 'subject', 'description')
            ->orderBy('name')
            ->get();

        return response()->json($templates);
    }

    /**
     * Process template variables.
     */
    private function processTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
