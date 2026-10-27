@props(['name', 'label', 'hint' => null, 'required' => false])
@php($invalid = $errors->has($name))
<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}@if($required)<span class="required-mark" aria-hidden="true">*</span>@endif</label>
    {{ $slot }}
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if($hint)<div class="form-hint">{{ $hint }}</div>@endif
</div>
