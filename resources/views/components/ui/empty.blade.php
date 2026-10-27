@props(['icon' => 'bi-inbox', 'title'])
<div class="empty">
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <h3>{{ $title }}</h3>
    {{ $slot }}
</div>
