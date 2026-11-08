{{-- Campo de formulario con etiqueta y error de validación. --}}
@props(['name', 'label' => null, 'type' => 'text', 'ayuda' => null])
<div>
    @if($label)<label for="{{ $name }}" class="label">{{ $label }}</label>@endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           @if($type !== 'password') value="{{ old($name, $attributes->get('value')) }}" @endif
           {{ $attributes->except('value')->merge(['class' => 'input-modern'.($errors->has($name) ? ' border-red-400' : '')]) }}
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @if($ayuda)<p class="text-xs text-gray-500 mt-1">{{ $ayuda }}</p>@endif
    @error($name)<p id="{{ $name }}-error" class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
