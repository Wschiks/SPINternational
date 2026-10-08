@props([
    'variant' => 'primary',
    'type' => 'submit',
])

@php
    $variants = [
        'primary' => 'border-cyan-300/60 bg-[#1d2442] text-cyan-100 hover:shadow-[0_0_18px_rgba(34,211,238,0.25)] focus:ring-cyan-300',
        'secondary' => 'border-white/20 bg-white/5 text-slate-200 hover:bg-white/10 focus:ring-white/40',
        'danger' => 'border-red-400/60 bg-red-600 hover:bg-red-500 focus:ring-red-400',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'arcade-btn inline-flex items-center justify-center gap-2 rounded-xl border px-5 py-3 text-xs font-black uppercase tracking-[0.15em] transition focus:outline-none focus:ring-2 '.($variants[$variant] ?? $variants['secondary'])]) }}>
    {{ $slot }}
</button>