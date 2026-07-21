<?php

namespace App\Http\Controllers;

use App\Http\Resources\EntityPersonResource;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        $preselectedPersonId = $request->integer('person_id') ?: null;

        return $this->formResponse('business-entities.partials.persons.form', [
            'businessEntity' => $businessEntity,
            'persons' => $this->personOptions(),
            'businessEntities' => $this->trusteeCompanyOptions($businessEntity),
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

        return $this->formResponse('business-entities.partials.persons.form', [
            'businessEntity' => $businessEntity,
            'persons' => $this->personOptions(),
            'businessEntities' => $this->trusteeCompanyOptions(
                $businessEntity,
                $entityPerson->entity_trustee_id ? (int) $entityPerson->entity_trustee_id : null
            ),
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

    public function destroy(BusinessEntity $businessEntity, EntityPerson $entityPerson): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $businessEntity);
        $this->ensureBelongsToEntity($businessEntity, $entityPerson);

        if ($entityPerson->role === 'Appointor') {
            if (request()->expectsJson()) {
                return $this->errorResponse('Appointor roles are managed via the company profile.');
            }

            return redirect()->route('business-entities.show', $businessEntity)
                ->withFragment('tab_persons')
                ->withErrors(['error' => 'Appointor roles are managed via the company profile.']);
        }

        $entityPerson->delete();
        $persons = $this->loadPersons($businessEntity);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Person role removed successfully.',
                'list_html' => $this->renderList($businessEntity, $persons),
                'persons' => EntityPersonResource::collection($persons)->resolve(),
                'labels' => $this->labels($businessEntity),
            ]);
        }

        return redirect()->route('business-entities.show', $businessEntity)
            ->withFragment('tab_persons')
            ->with('success', 'Person role removed successfully.');
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

    /**
     * Person names are encrypted at rest, so sort in PHP after decryption.
     */
    private function personOptions()
    {
        return Person::query()
            ->get()
            ->sortBy(fn (Person $person) => mb_strtolower($person->displayName()), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Corporate trustee candidates: operating non-trust companies, excluding the current entity.
     * Keep a previously selected trustee even if it later became a tenancy-only contact.
     */
    private function trusteeCompanyOptions(BusinessEntity $businessEntity, ?int $includeEntityId = null)
    {
        return BusinessEntity::query()
            ->where('entity_type', '!=', 'Trust')
            ->where('id', '!=', $businessEntity->id)
            ->where(function ($query) use ($includeEntityId) {
                $query->operationalEntities();

                if ($includeEntityId) {
                    $query->orWhere('id', $includeEntityId);
                }
            })
            ->orderBy('legal_name')
            ->get();
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
