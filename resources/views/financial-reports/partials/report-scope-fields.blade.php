@props(['report'])
@php
    $scope = $report['forms_scope'] ?? 'all';
@endphp
<input type="hidden" name="scope" value="{{ $scope }}">
@if($scope === 'selected')
    @foreach($report['forms_entity_ids'] ?? [] as $eid)
        <input type="hidden" name="entity_ids[]" value="{{ (int) $eid }}">
    @endforeach
@endif
