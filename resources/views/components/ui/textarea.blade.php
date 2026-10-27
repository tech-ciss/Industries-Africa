@props(['name', 'label', 'value' => null, 'hint' => null, 'required' => false, 'rows' => 5])
@php($id = $attributes->get('id', $name))
@php($invalid = $errors->has($name))
<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="required-mark" aria-hidden="true">*</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              @if($invalid) aria-invalid="true" @endif
              {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $invalid]) }}>{{ old($name, $value) }}</textarea>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if($hint)<div class="form-hint">{{ $hint }}</div>@endif
    {{ $extra ?? '' }}
</div>
