@props(['name', 'label', 'hint' => null, 'required' => false])
@php($id = $attributes->get('id', $name))
@php($invalid = $errors->has($name))
<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="required-mark" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
            @if($invalid) aria-invalid="true" @endif
            {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $invalid]) }}>{{ $slot }}</select>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if($hint)<div class="form-hint">{{ $hint }}</div>@endif
</div>
