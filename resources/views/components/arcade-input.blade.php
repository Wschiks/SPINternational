@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-xl border border-white/15 bg-[#0d0d24] px-4 py-3 text-sm text-white shadow-inner transition placeholder:text-slate-500 focus:border-cyan-300/70 focus:outline-none focus:ring-2 focus:ring-cyan-300/40 disabled:cursor-not-allowed disabled:opacity-50']) }}>