@props([
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
])

<div>
    <label for="{{ $name }}">{{ $label }}</label>
    <input
        id="{{ $name }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes }}
    >
</div>
