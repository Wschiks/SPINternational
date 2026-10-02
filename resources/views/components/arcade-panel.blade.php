@props(['tone' => 'default'])

@php
    $tones = [
        'default' => 'border-white/10 bg-[#14142d]',
        'danger' => 'border-red-400/30 bg-red-500/[0.06]',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border p-5 shadow-[0_18px_50px_rgba(0,0,0,0.45)] sm:p-7 '.($tones[$tone] ?? $tones['default'])]) }}>
    {{ $slot }}
</div>