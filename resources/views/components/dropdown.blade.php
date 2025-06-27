@props(['align' => 'right', 'width' => '48'])

@php
    switch ($align) {
        case 'left':
            $alignmentClasses = 'origin-top-left left-0';
            break;
        case 'top':
            $alignmentClasses = 'origin-top';
            break;
        case 'right':
        default:
            $alignmentClasses = 'origin-top-right right-0';
            break;
    }
@endphp

<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'relative']) }}>
    <!-- Trigger -->
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <!-- Dropdown Content -->
    <div
        x-show="open"
        @click.away="open = false"
        class="absolute z-50 mt-2 w-{{ $width }} rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 {{ $alignmentClasses }}"
        style="display: none;"
        x-cloak
    >
        <div class="py-1">
            {{ $content }}
        </div>
    </div>
</div> 