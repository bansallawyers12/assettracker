<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $templates = EmailTemplatesWorkspaceController::paginatedTemplates($request);

        return view('email-templates.index', compact('templates'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('email-templates.index');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EmailTemplate::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        EmailTemplate::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, __('Email template created successfully.'));
        }

        return redirect()->route('email-templates.index')
            ->with('success', __('Email template created successfully.'));
    }

    public function show(EmailTemplate $emailTemplate): RedirectResponse
    {
        return redirect()->route('email-templates.index');
    }

    public function edit(EmailTemplate $emailTemplate): RedirectResponse
    {
        return redirect()->route('email-templates.index');
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $emailTemplate);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $emailTemplate->update($validated);

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, __('Email template updated successfully.'));
        }

        return redirect()->route('email-templates.index')
            ->with('success', __('Email template updated successfully.'));
    }

    public function destroy(Request $request, EmailTemplate $emailTemplate): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $emailTemplate);

        $emailTemplate->delete();

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, __('Email template deleted successfully.'));
        }

        return redirect()->route('email-templates.index')
            ->with('success', __('Email template deleted successfully.'));
    }

    public function preview(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('view', $emailTemplate);

        $sampleData = [
            'recipient_name' => 'John Doe',
            'sender_name' => Auth::user()->name,
            'company_name' => 'Your Company',
            'current_date' => now()->format('Y-m-d'),
        ];

        return response()->json([
            'status' => true,
            'subject' => $this->processTemplate($emailTemplate->subject, $sampleData),
            'body' => $this->processTemplate($emailTemplate->description, $sampleData),
        ]);
    }

    public function getTemplates(): JsonResponse
    {
        $templates = EmailTemplate::query()
            ->select('id', 'name', 'subject', 'description')
            ->orderBy('name')
            ->get();

        return response()->json($templates);
    }

    private function workspaceJsonResponse(Request $request, string $message): JsonResponse
    {
        $templates = EmailTemplatesWorkspaceController::paginatedTemplates($request);

        return response()->json([
            'status' => true,
            'message' => $message,
            'list_html' => EmailTemplatesWorkspaceController::listHtml($templates),
        ]);
    }

    private function processTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
