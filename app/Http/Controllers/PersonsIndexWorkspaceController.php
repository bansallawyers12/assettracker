<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Person;
use App\Support\TableSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonsIndexWorkspaceController extends Controller
{
    public function workspace(Request $request): JsonResponse
    {
        $persons = self::paginatedPersons($request);

        return response()->json([
            'status' => true,
            'list_html' => self::listHtml($persons, $request),
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
        $tableSort = TableSort::resolve($request, ['name', 'email'], 'name', 'asc');

        $query = Person::query()
            ->with(['entityPersons.businessEntity'])
            ->has('entityPersons');

        $tableSort->applyToQuery($query, [
            'name' => ['last_name', 'first_name'],
            'email' => 'email',
        ], 'name');

        return $query
            ->paginate(15)
            ->withQueryString();
    }

    public static function tableSort(Request $request): TableSort
    {
        return TableSort::resolve($request, ['name', 'email'], 'name', 'asc');
    }

    public static function listHtml($persons, ?Request $request = null): string
    {
        $request ??= request();

        return view('persons.partials.list', [
            'persons' => $persons,
            'tableSort' => self::tableSort($request),
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
