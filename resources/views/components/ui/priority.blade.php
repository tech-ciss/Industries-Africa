@props(['priority'])
@php($icon = ['low' => 'bi-chevron-down', 'normal' => 'bi-dash-lg', 'high' => 'bi-chevron-up', 'critical' => 'bi-chevron-double-up'][$priority] ?? 'bi-dash-lg')
<span class="priority priority-{{ $priority }}"><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ \App\Models\Ticket::PRIORITY_LABELS[$priority] ?? $priority }}</span>
