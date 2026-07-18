<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonsIndexWorkspaceController extends Controller
{
    public function workspace(Request $request): JsonResponse
    {
        $persons = self::paginatedPersons($request);

        return response()->json([
            'status' => true,
            'list_html' => self::listHtml($persons),
            'stats_html' => self::statsHtml(),
        ]);
    }

    public function createForm(): JsonResponse
    {
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();

        return response()->json([
            'status' => true,
            'html' => view('persons.partials.create-form', [
                'businessEntities' => $businessEntities,
            ])->render(),
        ]);
    }

    public static function paginatedPersons(Request $request)
    {
        return Person::query()
            ->with(['entityPersons.businessEntity'])
            ->has('entityPersons')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();
    }

    public static function listHtml($persons): string
    {
        return view('persons.partials.list', [
            'persons' => $persons,
        ])->render();
    }

    public static function statsHtml(): string
    {
        $totalPersons = Person::has('entityPersons')->count();
        $activeRoles = EntityPerson::where('role_status', 'Active')->count();
        $multiRolePersons = Person::has('entityPersons', '>=', 2)->count();

        return view('persons.partials.stats', [
            'totalPersons' => $totalPersons,
            'activeRoles' => $activeRoles,
            'multiRolePersons' => $multiRolePersons,
        ])->render();
    }
}
