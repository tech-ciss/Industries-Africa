@props(['ticket'])
<ol class="rail" aria-label="Avancement du ticket">
    @foreach($ticket->workflowSteps() as $step)
        <li class="rail-step is-{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif>
            <span class="rail-dot">@if($step['state'] === 'done')<i class="bi bi-check-lg" aria-hidden="true"></i>@endif</span>
            <span class="rail-label">{{ $step['label'] }}</span>
            @if($step['at'] && $step['state'] !== 'todo')
                <span class="rail-date">{{ $step['at']->format('d/m H:i') }}</span>
            @endif
        </li>
    @endforeach
</ol>
