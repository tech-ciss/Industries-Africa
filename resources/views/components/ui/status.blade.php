@props(['status'])
<span class="status status-{{ $status }}">{{ \App\Models\Ticket::STATUS_LABELS[$status] ?? $status }}</span>
