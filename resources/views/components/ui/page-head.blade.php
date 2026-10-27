@props(['title', 'lead' => null])
<div class="page-head">
    <div>
        <h1>{{ $title }}</h1>
        @if($lead)<p class="lead-text">{{ $lead }}</p>@endif
    </div>
    @isset($actions)
        <div class="page-actions">{{ $actions }}</div>
    @endisset
</div>
