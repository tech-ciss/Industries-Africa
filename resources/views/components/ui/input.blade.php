@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'bag' => 'default'])
@php($id = $attributes->get('id', $name))
@php($invalid = $errors->getBag($bag)->has($name))
<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="required-mark" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @if(! in_array($type, ['password', 'file'])) value="{{ old($name, $value) }}" @endif
           @required($required)
           @if($hint) aria-describedby="{{ $id }}-hint" @endif
           @if($invalid) aria-invalid="true" @endif
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $invalid]) }}>
    @error($name, $bag)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if($hint)<div id="{{ $id }}-hint" class="form-hint">{{ $hint }}</div>@endif
</div>
