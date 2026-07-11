<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplatesWorkspaceController extends Controller
{
    public function workspace(Request $request): JsonResponse
    {
        $templates = self::paginatedTemplates($request);

        return response()->json([
            'status' => true,
            'list_html' => self::listHtml($templates),
        ]);
    }

    public function createForm(): JsonResponse
    {
        $this->authorize('create', EmailTemplate::class);

        return response()->json([
            'status' => true,
            'html' => view('email-templates.partials.form', [
                'template' => null,
            ])->render(),
        ]);
    }

    public function editForm(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('update', $emailTemplate);

        return response()->json([
            'status' => true,
            'html' => view('email-templates.partials.form', [
                'template' => $emailTemplate,
            ])->render(),
        ]);
    }

    public static function paginatedTemplates(Request $request)
    {
        return EmailTemplate::query()
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }

    public static function listHtml($templates): string
    {
        return view('email-templates.partials.list', [
            'templates' => $templates,
        ])->render();
    }
}
