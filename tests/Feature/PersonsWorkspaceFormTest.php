<?php

use App\Models\BusinessEntity;
use Tests\TestCase;

uses(TestCase::class);

it('renders the existing person selector when a person is preselected', function () {
    $businessEntity = new BusinessEntity;
    $businessEntity->id = 11;
    $businessEntity->entity_type = 'Company';
    $businessEntity->legal_name = 'Test Entity';

    $person = new class
    {
        public $id = 42;

        public $first_name = 'Jane';

        public $last_name = 'Doe';
    };

    $html = view('business-entities.partials.persons.form', [
        'businessEntity' => $businessEntity,
        'persons' => collect([$person]),
        'businessEntities' => collect(),
        'entityPerson' => null,
        'mode' => 'create',
        'preselectedPersonId' => $person->id,
    ])->render();

    expect($html)->toContain('id="persons_person_id"')
        ->and($html)->toContain('Select Existing Person')
        ->and($html)->toContain('value="42"')
        ->and($html)->toContain('data-tomselect-dropdown-parent="body"');
});

it('renders searchable person options for the add person workspace form', function () {
    $businessEntity = new BusinessEntity;
    $businessEntity->id = 13;
    $businessEntity->entity_type = 'Company';
    $businessEntity->legal_name = 'Charlie Sole Trader';

    $person = new class
    {
        public $id = 7;

        public $first_name = 'Sarah';

        public $last_name = 'Charlie';
    };

    $html = view('business-entities.partials.persons.form', [
        'businessEntity' => $businessEntity,
        'persons' => collect([$person]),
        'businessEntities' => collect(),
        'entityPerson' => null,
        'mode' => 'create',
        'preselectedPersonId' => null,
    ])->render();

    expect($html)->toContain('id="persons_person_id"')
        ->and($html)->toContain('data-tomselect-dropdown-parent="body"')
        ->and($html)->toContain('value="7"')
        ->and($html)->toContain('Sarah Charlie')
        ->and($html)->toContain('id="persons_create_new_person"')
        ->and($html)->not->toContain('data-tomselect-skip');
});

it('renders persons index create form with dropdown-parent=body on entity selector', function () {
    $entity = new BusinessEntity;
    $entity->id = 5;
    $entity->entity_type = 'Company';
    $entity->legal_name = 'Acme Holdings';

    $html = view('persons.partials.create-form', [
        'businessEntities' => collect([$entity]),
    ])->render();

    expect($html)->toContain('id="person_business_entity_id"')
        ->and($html)->toContain('data-tomselect-dropdown-parent="body"');
});
