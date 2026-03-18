@props(['permission'])

<span {{ $attributes->merge(['class' => 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800']) }}>
    {{ $permission }}
</span>
