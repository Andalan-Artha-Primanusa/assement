@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center w-full px-3 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 border-r-2 border-indigo-400 rounded-lg transition duration-150 ease-in-out'
            : 'flex items-center w-full px-3 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 border-r-2 border-transparent rounded-lg transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
