<div class="fixed left-3 top-3 z-50 sm:left-5 sm:top-5"
     x-data="siteMenu"
     @keydown.escape.window="menuOpen && closeMenu()"
     @toggle-site-menu.window="toggleMenu($event.detail)"
     @game-state-updated.window="updateGameState($event.detail)">
    <button type="button"
            x-ref="menuButton"
            @click="toggleMenu()"
            :aria-expanded="menuOpen.toString()"
            :aria-label="menuOpen ? 'Menu sluiten' : 'Menu openen'"
            aria-controls="site-menu-panel"
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
         @click="closeMenu()"></div>

    <nav id="site-menu-panel" aria-label="Spelmenu"
         x-ref="menuPanel" tabindex="-1"
         x-show="menuOpen" x-cloak
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="-translate-x-3 opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="-translate-x-3 opacity-0"
         class="absolute left-0 top-14 z-50 flex max-h-[calc(100vh-5rem)] w-[min(23rem,calc(100vw-1.5rem))] flex-col overflow-hidden rounded-2xl border border-indigo-400/70 bg-[#14142d] text-white shadow-[0_20px_60px_rgba(0,0,0,0.7),0_0_26px_rgba(99,102,241,0.35)] focus:outline-none">
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
            <button type="button" x-show="activePanel !== 'main'" @click="showPanel('main')"
                    class="rounded-lg border border-white/10 px-2.5 py-2 text-xs font-black text-cyan-200 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-cyan-300"
                    aria-label="Terug naar menu">&larr;</button>
            <img x-show="activePanel === 'main'" src="{{ asset('images/game/logo.png') }}" alt="" class="h-11 w-14 object-contain">
            <div class="min-w-0">
                <p x-ref="panelHeading" tabindex="-1" class="truncate text-xs font-black tracking-wider text-white focus:outline-none"
                   x-text="activePanel === 'main' ? 'SPINternational' : { rules: 'Spelregels', tutorial: 'Tutorial', history: 'Scorehistorie', settings: 'Instellingen' }[activePanel]"></p>
                <p class="text-[10px] uppercase tracking-[0.2em] text-cyan-300" x-text="activePanel === 'main' ? 'Spelmenu' : 'Menu'"></p>
            </div>
            <button type="button" @click="closeMenu()" class="ml-auto rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-300" aria-label="Menu sluiten">X</button>
        </div>

        <div class="min-h-0 overflow-y-auto p-3">
            <div x-show="activePanel === 'main'" class="flex flex-col gap-2">
                <button type="button" @click="showPanel('rules')" class="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-left transition hover:border-cyan-300/40 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-cyan-300">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-400/15 text-sm font-black text-cyan-200" aria-hidden="true">?</span>
                    <span><span class="block text-sm font-black">Spelregels</span><span class="block text-xs text-slate-400">Zo speel en win je</span></span>
                    <span class="ml-auto text-cyan-300" aria-hidden="true">&rarr;</span>
                </button>
                <button type="button" @click="showPanel('tutorial')" class="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-left transition hover:border-fuchsia-300/40 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-fuchsia-400/15 text-[10px] font-black text-fuchsia-200" aria-hidden="true">1-5</span>
                    <span><span class="block text-sm font-black">Tutorial</span><span class="block text-xs text-slate-400">Rondleiding met tooltips</span></span>
                    <span class="ml-auto text-fuchsia-300" aria-hidden="true">&rarr;</span>
                </button>
                <button type="button" @click="showPanel('history')" class="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-left transition hover:border-amber-300/40 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-amber-300">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-400/15 text-sm font-black text-amber-200" aria-hidden="true">+/-</span>
                    <span><span class="block text-sm font-black">Scorehistorie</span><span class="block text-xs text-slate-400">Bekijk je laatste mutaties</span></span>
                    <span class="ml-auto text-amber-300" aria-hidden="true">&rarr;</span>
                </button>
                <button type="button" @click="showPanel('settings')" class="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-left transition hover:border-emerald-300/40 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-400/15 text-sm font-black text-emerald-200" aria-hidden="true">UI</span>
                    <span><span class="block text-sm font-black">Instellingen</span><span class="block text-xs text-slate-400">Pas de spelweergave aan</span></span>
                    <span class="ml-auto text-emerald-300" aria-hidden="true">&rarr;</span>
                </button>
            </div>

            <section x-show="activePanel === 'rules'" class="flex flex-col gap-3 px-1 py-2 text-sm leading-relaxed text-slate-200">
                <div class="rounded-xl bg-white/5 p-3"><strong class="text-cyan-200">1. Spin</strong><span class="mt-1 block text-xs text-slate-300">Elke draai kost 10 punten. Minstens één rol moet vrij zijn.</span></div>
                <div class="rounded-xl bg-white/5 p-3"><strong class="text-fuchsia-200">2. Hold</strong><span class="mt-1 block text-xs text-slate-300">Houd een rol vast en beantwoord de vraag om het symbool te vergrendelen.</span></div>
                <div class="rounded-xl bg-white/5 p-3"><strong class="text-amber-200">3. Verdien punten</strong><span class="mt-1 block text-xs text-slate-300">Goede antwoorden leveren 10 tot 50 punten op. SKIP en REJECT kosten punten.</span></div>
                <div class="rounded-xl bg-white/5 p-3"><strong class="text-emerald-200">4. Win</strong><span class="mt-1 block text-xs text-slate-300">Vul het badgeboard, activeer thema's en voltooi alle thema-opdrachten.</span></div>
            </section>

            <section x-show="activePanel === 'tutorial'" class="flex flex-col gap-4 px-1 py-2">
                <div class="rounded-xl border border-fuchsia-300/20 bg-fuchsia-400/10 p-4">
                    <p class="text-sm font-black text-fuchsia-100">Interactieve rondleiding</p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-300">Vijf korte tooltips wijzen MENU, score, rollen, HOLD en SPIN op het speelveld aan.</p>
                </div>
                @if (request()->routeIs('game.play', 'dashboard'))
                    <button type="button" @click="startTutorial()" :disabled="!sessionId" class="arcade-btn rounded-xl border-fuchsia-300 bg-fuchsia-600 px-4 py-3 text-sm font-black text-white disabled:cursor-not-allowed disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Start tutorial</button>
                    <p x-show="!sessionId" class="text-xs leading-relaxed text-amber-200">Start eerst een spel; daarna kan de tutorial de speelknoppen aanwijzen.</p>
                @else
                    @auth
                        <a href="{{ route('game.play') }}" class="arcade-btn rounded-xl border-fuchsia-300 bg-fuchsia-600 px-4 py-3 text-center text-sm font-black text-white focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Ga naar het spel</a>
                    @else
                        <a href="{{ route('login') }}" class="arcade-btn rounded-xl border-fuchsia-300 bg-fuchsia-600 px-4 py-3 text-center text-sm font-black text-white focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Log in om te spelen</a>
                    @endauth
                @endif
            </section>

            <section x-show="activePanel === 'history'" class="flex flex-col gap-3 px-1 py-2">
                <div x-show="sessionId" class="flex items-center justify-between rounded-xl border border-amber-300/20 bg-amber-400/10 p-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-100">Huidige score</span>
                    <strong class="text-xl text-white" x-text="currentScore"></strong>
                </div>
                <p x-show="!sessionId" class="rounded-xl bg-white/5 p-4 text-xs leading-relaxed text-slate-300">Start een spel om de scorehistorie van je huidige ronde te zien.</p>
                <p x-show="sessionId && scoreHistory.length === 0" class="rounded-xl bg-white/5 p-4 text-xs text-slate-300">Er zijn nog geen scoremutaties.</p>
                <ol x-show="scoreHistory.length > 0" class="flex flex-col gap-2">
                    <template x-for="event in scoreHistory" :key="event.id">
                        <li class="grid grid-cols-[1fr_auto] items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5">
                            <span class="min-w-0"><span class="block truncate text-xs font-bold text-white" x-text="historyLabel(event.event_type)"></span><time class="block text-[10px] text-slate-400" x-text="formatEventTime(event.timestamp)"></time></span>
                            <span class="text-right"><strong class="block text-sm" :class="event.points_delta >= 0 ? 'text-emerald-300' : 'text-orange-300'" x-text="`${event.points_delta > 0 ? '+' : ''}${event.points_delta}`"></strong><span class="block text-[10px] text-slate-400" x-text="`${event.cumulative_score} totaal`"></span></span>
                        </li>
                    </template>
                </ol>
            </section>

            <section x-show="activePanel === 'settings'" class="flex flex-col gap-2 px-1 py-2">
                <template x-for="setting in [
                    { key: 'animationsEnabled', label: 'Animaties', help: 'Confetti en bewegende effecten' },
                    { key: 'buttonHintsEnabled', label: 'Knophints', help: 'Korte uitleg bij aanwijzen of focussen' },
                    { key: 'highContrastEnabled', label: 'Hoog contrast', help: 'Extra duidelijke randen en focus' },
                    { key: 'autoTutorialEnabled', label: 'Tutorial bij eerste spel', help: 'Start de rondleiding automatisch' },
                ]" :key="setting.key">
                    <button type="button" role="switch" :aria-checked="$data[setting.key].toString()" @click="toggleSetting(setting.key)" class="flex w-full items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/5 p-3 text-left hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        <span><span class="block text-sm font-bold text-white" x-text="setting.label"></span><span class="mt-0.5 block text-xs text-slate-400" x-text="setting.help"></span></span>
                        <span class="relative h-6 w-11 shrink-0 rounded-full transition" :class="$data[setting.key] ? 'bg-emerald-500' : 'bg-slate-600'" aria-hidden="true"><span class="absolute top-1 h-4 w-4 rounded-full bg-white transition-transform" :class="$data[setting.key] ? 'translate-x-6' : 'translate-x-1'"></span></span>
                    </button>
                </template>
            </section>
        </div>

        <div x-show="activePanel === 'main'" class="flex flex-col gap-1 border-t border-white/10 p-3">
            <a href="{{ route('home') }}" @click="closeMenu(false)" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-cyan-200 focus:outline-none focus:ring-2 focus:ring-cyan-300">Startpagina</a>
            @auth
                <a href="{{ route('game.play') }}" @click="closeMenu(false)" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-cyan-200 focus:outline-none focus:ring-2 focus:ring-cyan-300">Speel het spel</a>
                <a href="{{ route('profile.edit') }}" @click="closeMenu(false)" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-cyan-200 focus:outline-none focus:ring-2 focus:ring-cyan-300">Mijn profiel</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-orange-200 transition hover:bg-orange-400/10 focus:outline-none focus:ring-2 focus:ring-orange-300">Uitloggen</button>
                </form>
            @else
                <a href="{{ route('login') }}" @click="closeMenu(false)" class="rounded-lg bg-fuchsia-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-fuchsia-300">Inloggen</a>
                <a href="{{ route('register') }}" @click="closeMenu(false)" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-cyan-200 focus:outline-none focus:ring-2 focus:ring-cyan-300">Account aanmaken</a>
            @endauth
        </div>
        <div class="hidden">
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

    </nav>
</div>
