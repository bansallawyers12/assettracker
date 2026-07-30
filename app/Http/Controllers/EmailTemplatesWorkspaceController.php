<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Support\TableSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplatesWorkspaceController extends Controller
{
    public function workspace(Request $request): JsonResponse
    {
        $templates = self::paginatedTemplates($request);

        return response()->json([
            'status' => true,
            'list_html' => self::listHtml($templates, $request),
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
        $tableSort = TableSort::resolve($request, ['name', 'subject', 'updated'], 'name', 'asc');

        $query = EmailTemplate::query();
        $tableSort->applyToQuery($query, [
            'name' => 'name',
            'subject' => 'subject',
            'updated' => 'updated_at',
        ], 'name');

        return $query
            ->paginate(12)
            ->withQueryString();
    }

    public static function tableSort(Request $request): TableSort
    {
        return TableSort::resolve($request, ['name', 'subject', 'updated'], 'name', 'asc');
    }

    public static function listHtml($templates, ?Request $request = null): string
    {
        $request ??= request();

        return view('email-templates.partials.list', [
            'templates' => $templates,
            'tableSort' => self::tableSort($request),
        ])->render();
    }
}
