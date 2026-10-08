<div class="fixed left-3 top-3 z-50 sm:left-5 sm:top-5"
     x-data="{ menuOpen: false }"
     @keydown.escape.window="menuOpen = false"
     @toggle-site-menu.window="menuOpen = !menuOpen">
    <button type="button"
            x-ref="menuButton"
            @click="menuOpen = !menuOpen"
            :aria-expanded="menuOpen.toString()"
            aria-controls="site-menu-panel"
            aria-label="Open menu"
            class="arcade-btn relative z-50 flex h-11 items-center gap-3 border border-cyan-300/70 bg-[#17142f] px-3 text-cyan-100 shadow-[0_0_18px_rgba(34,211,238,0.25)] focus:outline-none focus:ring-2 focus:ring-fuchsia-400 sm:px-4">
        <span class="flex w-5 flex-col gap-1" aria-hidden="true">
            <span class="h-0.5 w-5 rounded bg-cyan-200 transition-transform" :class="menuOpen ? 'translate-y-1.5 rotate-45' : ''"></span>
            <span class="h-0.5 w-5 rounded bg-cyan-200 transition-opacity" :class="menuOpen ? 'opacity-0' : ''"></span>
            <span class="h-0.5 w-5 rounded bg-cyan-200 transition-transform" :class="menuOpen ? '-translate-y-1.5 -rotate-45' : ''"></span>
        </span>
        <span class="hidden text-xs font-black tracking-[0.2em] sm:inline">MENU</span>
    </button>

    <div x-show="menuOpen" x-cloak x-transition.opacity
         class="fixed inset-0 z-40 bg-[#050511]/80 backdrop-blur-sm"
         @click="menuOpen = false"></div>

    <nav id="site-menu-panel" aria-label="Hoofdnavigatie"
         x-show="menuOpen" x-cloak
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="-translate-x-3 opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="-translate-x-3 opacity-0"
         class="absolute left-0 top-14 z-50 w-[min(20rem,calc(100vw-1.5rem))] overflow-hidden rounded-2xl border border-indigo-400/70 bg-[#14142d] p-3 text-white shadow-[0_20px_60px_rgba(0,0,0,0.7),0_0_26px_rgba(99,102,241,0.35)]">
        <div class="flex items-center gap-3 border-b border-white/10 px-2 pb-3">
            <img src="{{ asset('images/game/logo.png') }}" alt="" class="h-12 w-16 object-contain">
            <div>
                <p class="text-xs font-black tracking-wider text-white">SPINternational</p>
                <p class="text-[10px] uppercase tracking-[0.25em] text-cyan-300">Speel & ontdek</p>
            </div>
        </div>

        <div class="flex flex-col gap-1.5 py-3">
            <a href="{{ route('home') }}" @click="menuOpen = false"
               class="rounded-lg px-4 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10 hover:text-cyan-200 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-cyan-300">Startpagina</a>
            @auth
                <a href="{{ route('game.play') }}" @click="menuOpen = false"
                   class="rounded-lg bg-fuchsia-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Speel het spel <span aria-hidden="true">↗</span></a>
                <a href="{{ route('profile.edit') }}" @click="menuOpen = false"
                   class="rounded-lg px-4 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10 hover:text-cyan-200 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-cyan-300">Mijn profiel</a>
            @else
                <a href="{{ route('login') }}" @click="menuOpen = false"
                   class="rounded-lg bg-fuchsia-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Inloggen <span aria-hidden="true">↗</span></a>
                <a href="{{ route('register') }}" @click="menuOpen = false"
                   class="rounded-lg px-4 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10 hover:text-cyan-200 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-cyan-300">Account aanmaken</a>
            @endauth
            @env('local')
                <button type="button"
                        x-data="{ on: localStorage.getItem('devShowAnswers') === '1' }"
                        @click="on = !on; localStorage.setItem('devShowAnswers', on ? '1' : '0'); $dispatch('dev-show-answers', on)"
                        class="rounded-lg border border-dashed border-yellow-400/60 px-4 py-3 text-left text-sm font-semibold text-yellow-200 transition hover:bg-yellow-400/10">
                    ⭐ DEV <span x-text="on ? 'AAN' : 'UIT'" :class="on ? 'text-green-300' : 'text-red-400'"></span>
                </button>
            @endenv
        </div>

        @auth
            <div class="border-t border-white/10 px-2 pt-3">
                <p class="mb-2 truncate text-xs text-slate-400">Ingelogd als {{ Auth::user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-orange-400/50 px-4 py-2.5 text-left text-sm font-semibold text-orange-200 transition hover:bg-orange-400/10 focus:outline-none focus:ring-2 focus:ring-orange-300">Uitloggen</button>
                </form>
            </div>
        @endauth
    </nav>
</div>
