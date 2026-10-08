@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-[10px] font-black uppercase tracking-[0.25em] text-cyan-300/80']) }}>
    {{ $value ?? $slot }}
</label>