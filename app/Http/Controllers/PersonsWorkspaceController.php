<?php

namespace App\Http\Controllers;

use App\Http\Resources\EntityPersonResource;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonsWorkspaceController extends Controller
{
    public function index(Request $request, BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        $persons = $this->loadPersons($businessEntity);

        return response()->json([
            'status' => true,
            'entity' => [
                'id' => $businessEntity->id,
                'legal_name' => $businessEntity->legal_name,
                'is_trust' => $businessEntity->isTrust(),
            ],
            'persons' => EntityPersonResource::collection($persons)->resolve(),
            'list_html' => $this->renderList($businessEntity, $persons),
            'labels' => $this->labels($businessEntity),
        ]);
    }

    public function createForm(BusinessEntity $businessEntity, Request $request): JsonResponse|View
    {
        $this->authorize('update', $businessEntity);

        if ($businessEntity->isTenancyContactOnly()) {
            return $this->errorResponse('Company roles and officers apply to operating entities only, not tenancy or property manager contacts.');
        }

        $persons = Person::orderBy('first_name')->orderBy('last_name')->get();
        $businessEntities = BusinessEntity::operationalEntities()
            ->where('entity_type', '!=', 'Trust')
            ->orderBy('legal_name')
            ->get();

        $preselectedPersonId = $request->integer('person_id') ?: null;

        return $this->formResponse('business-entities.partials.persons.form', [
            'businessEntity' => $businessEntity,
            'persons' => $persons,
            'businessEntities' => $businessEntities,
            'entityPerson' => null,
            'mode' => 'create',
            'preselectedPersonId' => $preselectedPersonId,
        ]);
    }

    public function editForm(BusinessEntity $businessEntity, EntityPerson $entityPerson): JsonResponse|View
    {
        $this->authorize('update', $businessEntity);
        $this->ensureBelongsToEntity($businessEntity, $entityPerson);

        $entityPerson->load(['person', 'trusteeEntity', 'appointorEntity']);
        $persons = Person::orderBy('first_name')->orderBy('last_name')->get();
        $businessEntities = BusinessEntity::operationalEntities()
            ->where('entity_type', '!=', 'Trust')
            ->orderBy('legal_name')
            ->get();

        return $this->formResponse('business-entities.partials.persons.form', [
            'businessEntity' => $businessEntity,
            'persons' => $persons,
            'businessEntities' => $businessEntities,
            'entityPerson' => $entityPerson,
            'mode' => 'edit',
        ]);
    }

    public function showDetail(BusinessEntity $businessEntity, EntityPerson $entityPerson): JsonResponse|View
    {
        $this->authorize('view', $businessEntity);
        $this->ensureBelongsToEntity($businessEntity, $entityPerson);

        $entityPerson->load(['person', 'trusteeEntity', 'appointorEntity']);

        $detailView = request()->boolean('person_show')
            ? 'persons.partials.roles.detail'
            : 'business-entities.partials.persons.detail';

        if (request()->expectsJson()) {
            return response()->json([
                'status' => true,
                'entity_person' => (new EntityPersonResource($entityPerson))->resolve(),
                'html' => view($detailView, [
                    'businessEntity' => $businessEntity,
                    'entityPerson' => $entityPerson,
                ])->render(),
            ]);
        }

        return view($detailView, compact('businessEntity', 'entityPerson'));
    }

    private function loadPersons(BusinessEntity $businessEntity)
    {
        return $businessEntity->persons()
            ->with(['person', 'trusteeEntity'])
            ->orderBy('role')
            ->orderBy('id')
            ->get();
    }

    private function renderList(BusinessEntity $businessEntity, $persons): string
    {
        return view('business-entities.partials.persons.list', [
            'businessEntity' => $businessEntity,
            'persons' => $persons,
        ])->render();
    }

    private function labels(BusinessEntity $businessEntity): array
    {
        $isTrust = $businessEntity->isTrust();

        return [
            'add' => $isTrust ? 'Add Person/Company' : 'Add Person',
            'empty_cta' => $isTrust ? 'Add your first person or company' : 'Add your first person',
        ];
    }

    private function formResponse(string $view, array $data): JsonResponse|View
    {
        if (request()->expectsJson()) {
            return response()->json([
                'status' => true,
                'html' => view($view, $data)->render(),
            ]);
        }

        return view($view, $data);
    }

    private function ensureBelongsToEntity(BusinessEntity $businessEntity, EntityPerson $entityPerson): void
    {
        if ((int) $entityPerson->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }

    private function errorResponse(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $status);
    }
}
