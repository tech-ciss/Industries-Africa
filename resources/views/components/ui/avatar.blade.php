@props(['user', 'size' => null])
<span class="avatar {{ $size ? 'avatar-'.$size : '' }}" style="--h: {{ ($user->id * 47) % 360 }}" aria-hidden="true">{{ $user->initials }}</span>
