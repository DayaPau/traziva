{{-- Lista desplegable. :opciones = [valor => texto]. --}}
@props(['name', 'label' => null, 'opciones' => [], 'vacio' => null, 'valor' => null])
@php $actual = (string) old($name, $valor); @endphp
<div>
    @if($label)<label for="{{ $name }}" class="label">{{ $label }}</label>@endif
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'input-modern'.($errors->has($name) ? ' border-red-400' : '')]) }}>
        @if($vacio !== null)<option value="">{{ $vacio }}</option>@endif
        @foreach($opciones as $v => $texto)
            <option value="{{ $v }}" @selected($actual === (string) $v)>{{ $texto }}</option>
        @endforeach
    </select>
    @error($name)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
