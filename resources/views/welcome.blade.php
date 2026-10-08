<x-bare-layout>
    <x-site-menu />

    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[radial-gradient(circle_at_50%_25%,#34205d_0%,#13132d_45%,#070710_100%)] px-5 py-24 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(103,232,249,0.15)_1px,transparent_1px),linear-gradient(90deg,rgba(103,232,249,0.15)_1px,transparent_1px)] [background-size:48px_48px]" aria-hidden="true"></div>
        <div class="relative mx-auto flex w-full max-w-3xl flex-col items-center gap-7 text-center">
            <p class="rounded-full border border-cyan-400/40 bg-cyan-400/10 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.3em] text-cyan-200">De educatieve fruitmachine</p>
            <img src="{{ asset('images/game/logo.png') }}" alt="SPINternational" class="w-full max-w-sm drop-shadow-[0_0_35px_rgba(236,72,153,0.45)] sm:max-w-md">
            <div class="flex max-w-xl flex-col gap-4">
                <h1 class="text-3xl font-black leading-tight sm:text-5xl">Spin. Leer. Win.</h1>
                <p class="text-base leading-relaxed text-slate-300 sm:text-lg">Draai de rollen, beantwoord vragen en verzamel punten. Elk spel brengt je dichter bij de overwinning.</p>
            </div>
            <div class="flex w-full max-w-md flex-col gap-3 sm:flex-row sm:justify-center">
                @auth
                    <a href="{{ route('game.play') }}" class="arcade-btn rounded-xl border-fuchsia-300 bg-fuchsia-600 px-7 py-3.5 text-sm font-black text-white shadow-[0_0_25px_rgba(217,70,239,0.45)] hover:bg-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Verder spelen</a>
                    <a href="{{ route('profile.edit') }}" class="arcade-btn rounded-xl border-cyan-300/60 bg-[#1d2442] px-7 py-3.5 text-sm font-bold text-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-300">Mijn profiel</a>
                @else
                    <a href="{{ route('login') }}" class="arcade-btn rounded-xl border-fuchsia-300 bg-fuchsia-600 px-7 py-3.5 text-sm font-black text-white shadow-[0_0_25px_rgba(217,70,239,0.45)] hover:bg-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Inloggen en spelen</a>
                    <a href="{{ route('register') }}" class="arcade-btn rounded-xl border-cyan-300/60 bg-[#1d2442] px-7 py-3.5 text-sm font-bold text-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-300">Account aanmaken</a>
                @endauth
            </div>
            <div class="grid w-full max-w-2xl gap-3 pt-5 text-left sm:grid-cols-3">
                <div class="rounded-xl border border-cyan-300/20 bg-white/5 p-4"><span class="text-xl" aria-hidden="true">🎰</span><h2 class="mt-2 text-sm font-bold">Spin</h2><p class="mt-1 text-xs leading-relaxed text-slate-400">Zoek matches op de rollen.</p></div>
                <div class="rounded-xl border border-fuchsia-300/20 bg-white/5 p-4"><span class="text-xl" aria-hidden="true">💡</span><h2 class="mt-2 text-sm font-bold">Leer</h2><p class="mt-1 text-xs leading-relaxed text-slate-400">Beantwoord verrassende vragen.</p></div>
                <div class="rounded-xl border border-amber-300/20 bg-white/5 p-4"><span class="text-xl" aria-hidden="true">🏆</span><h2 class="mt-2 text-sm font-bold">Win</h2><p class="mt-1 text-xs leading-relaxed text-slate-400">Verzamel punten en thema's.</p></div>
            </div>
        </div>
    </main>
</x-bare-layout>
