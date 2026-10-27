@props(['date' => null])
@if($date)
    <time datetime="{{ $date->toIso8601String() }}" title="{{ $date->format('d/m/Y à H:i') }}">{{ $date->copy()->locale('fr')->diffForHumans() }}</time>
@else
    <span class="text-soft">-</span>
@endif
