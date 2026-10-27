@props(['user' => null, 'empty' => 'Non assigné'])
@if($user)
    <span class="person"><x-ui.avatar :user="$user" size="sm" /><span>{{ $user->full_name }}</span></span>
@else
    <span class="text-soft">{{ $empty }}</span>
@endif
