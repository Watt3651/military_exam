@props(['role'])

<span {{ $attributes->merge(['class' => 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . \App\Models\User::roleBadgeClasses($role)]) }}>
    {{ \App\Models\User::roleLabel($role) }}
</span>
