<x-bare-layout>
    @php
        $assetPaths = [
            'badgeboard' => asset('images/game/badgeboard'),
            'checkboxes' => asset('images/game/checkboxes'),
            'themes' => [
                1 => asset('images/game/themes/duurzaamheid.png'),
                2 => asset('images/game/themes/ict.png'),
                3 => asset('images/game/themes/inclusie.png'),
                4 => asset('images/game/themes/wereldburger.png'),
            ],
            'led' => [
                'red' => asset('images/game/led/klaver.png'),
                'blue' => asset('images/game/led/duim.png'),
                'pink' => asset('images/game/led/regenboog.png'),
                'yellow' => asset('images/game/led/vuur.png'),
                'green' => asset('images/game/led/feest.png'),
            ],
        ];
    @endphp

    <x-site-menu />
    <div class="min-h-screen flex items-center justify-center py-6"
         x-data='gameApp(@json($categories), @json($themes), @json($ledKrans), @json($assetPaths), { spinCost: @json($spinCost) })'
         @dev-show-answers.window="devShowAnswers = $event.detail">
        <div class="game-confetti-layer" aria-hidden="true">
            <template x-for="piece in confetti" :key="piece.id">
                <span class="game-confetti-piece" :style="piece.style"></span>
            </template>
        </div>
        <div class="max-w-md mx-auto px-3">
            <div class="game-cabinet relative rounded-[2rem] bg-black text-white p-3 shadow-[0_0_25px_rgba(99,102,241,0.5)] spin-font"
                 :class="{ 'game-spinning': spinning, 'game-celebrating': celebration !== null }">

                <!-- START SCREEN -->
                <template x-if="!started">
                    <div class="flex flex-col items-center justify-center gap-6 py-16 text-center">
                        <img src="{{ asset('images/game/logo.png') }}" alt="SPINternational" class="w-56 drop-shadow-[0_0_15px_rgba(255,0,110,0.6)]">
                        <p class="text-[10px] text-gray-400 max-w-xs leading-relaxed">Educatieve fruitmachine — beantwoord vragen, activeer thema's, win het spel.</p>
                        <div class="rounded-lg border border-cyan-400/40 bg-cyan-950 p-4 text-xs leading-relaxed">
                            <p>Je begint met <strong>{{ $startingPoints }} punten</strong>.</p>
                            <p>Elke spin kost <strong>{{ $spinCost }} punten</strong>.</p>
                            <p class="text-green-300">Goed antwoord: +10 tot +50 punten.</p>
                        </div>
                        <div class="flex items-center gap-2 text-[10px]">
                            <span>Level:</span>
                            <template x-for="lvl in [1,2,3]" :key="lvl">
                                <button type="button" @click="startLevel = lvl"
                                        class="px-3 py-2 rounded border"
                                        :class="startLevel === lvl ? 'bg-cyan-400 text-black border-cyan-400' : 'border-gray-600 text-gray-300'"
                                        x-text="lvl"></button>
                            </template>
                        </div>
                        <button type="button" @click="startGame()"
                                class="px-8 py-3 rounded-lg bg-fuchsia-600 hover:bg-fuchsia-500 text-white text-xs neon-box">
                            START SPEL
                        </button>
                    </div>
                </template>

                <template x-if="started">
                <div>
                    <!-- BLOK 6: KROON -->
                    <div class="relative rounded-t-2xl border-2 border-b-0 border-indigo-400 bg-gradient-to-b from-[#1a1a3a] to-[#141428] px-2 pt-3 pb-2 grid gap-x-1" style="grid-template-columns: 1fr 1.7fr 1fr;">
                        <div class="flex flex-col items-center justify-start">
                            <button type="button" class="w-14 h-14 sm:w-16 sm:h-16" @click="onThemeIconClick(1)">
                                <img :src="themeIconUrl(1)" class="w-full h-full object-contain transition" :class="themeImgClasses(1)" :style="themeImgStyle(1)">
                            </button>
                            <div class="flex gap-1 mt-1" x-show="themes[1].active">
                                <template x-for="i in [1,2,3]" :key="'c1'+i">
                                    <img :src="checkboxUrl(1,i)" class="w-3 h-3">
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-center">
                            <img src="{{ asset('images/game/logo.png') }}" class="w-full max-w-[210px] drop-shadow-[0_0_10px_rgba(255,0,110,0.6)]">
                        </div>

                        <div class="flex flex-col items-center justify-start">
                            <button type="button" class="w-14 h-14 sm:w-16 sm:h-16" @click="onThemeIconClick(3)">
                                <img :src="themeIconUrl(3)" class="w-full h-full object-contain transition" :class="themeImgClasses(3)" :style="themeImgStyle(3)">
                            </button>
                            <div class="flex gap-1 mt-1" x-show="themes[3].active">
                                <template x-for="i in [1,2,3]" :key="'c3'+i">
                                    <img :src="checkboxUrl(3,i)" class="w-3 h-3">
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-start mt-2">
                            <button type="button" class="w-14 h-14 sm:w-16 sm:h-16" @click="onThemeIconClick(2)">
                                <img :src="themeIconUrl(2)" class="w-full h-full object-contain transition" :class="themeImgClasses(2)" :style="themeImgStyle(2)">
                            </button>
                            <div class="flex gap-1 mt-1" x-show="themes[2].active">
                                <template x-for="i in [1,2,3]" :key="'c2'+i">
                                    <img :src="checkboxUrl(2,i)" class="w-3 h-3">
                                </template>
                            </div>
                        </div>
                        <div></div>
                        <div class="flex flex-col items-center justify-start mt-2">
                            <button type="button" class="w-14 h-14 sm:w-16 sm:h-16" @click="onThemeIconClick(4)">
                                <img :src="themeIconUrl(4)" class="w-full h-full object-contain transition" :class="themeImgClasses(4)" :style="themeImgStyle(4)">
                            </button>
                            <div class="flex gap-1 mt-1" x-show="themes[4].active">
                                <template x-for="i in [1,2,3]" :key="'c4'+i">
                                    <img :src="checkboxUrl(4,i)" class="w-3 h-3">
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- BLOK 5: BADGEBOARD -->
                    <div class="border-x-2 border-indigo-400 bg-gradient-to-b from-[#141428] to-[#0d0d1f] pb-2">
                        <div class="mx-auto py-2" style="width:60%;">
                            <div class="rounded-lg border-2 border-slate-600 bg-gradient-to-b from-[#4a1218] via-[#2a0a0e] to-[#1a0508] p-1.5">
                                <div class="grid grid-cols-5 gap-1 mb-1">
                                    <template x-for="col in [1,2,3,4,5]" :key="'arrow-top'+col">
                                        <div class="flex justify-center text-[8px] leading-none" :class="col===3 ? 'opacity-0' : 'text-green-400'">▲</div>
                                    </template>
                                </div>
                                <div class="grid grid-cols-5 gap-1">
                                    <template x-for="row in [1,2,3,4]" :key="'row'+row">
                                        <template x-for="col in [1,2,3,4,5]" :key="'cell'+row+'-'+col">
                                            <div class="aspect-square rounded flex items-center justify-center p-1 bg-black/20 border border-black/40">
                                                <template x-if="col === 3">
                                                    <img :src="badgeMiddleUrl(row)" class="w-full h-full object-contain">
                                                </template>
                                                <template x-if="col !== 3">
                                                    <img :src="categoryIconUrl(categoryAt(row,col))" class="w-full h-full object-contain transition"
                                                         :class="badgeOn(row,col) ? 'opacity-100' : 'opacity-30 grayscale'">
                                                </template>
                                            </div>
                                        </template>
                                    </template>
                                </div>
                                <div class="grid grid-cols-5 gap-1 mt-1">
                                    <template x-for="col in [1,2,3,4,5]" :key="'arrow-bot'+col">
                                        <div class="flex justify-center text-[8px] leading-none" :class="col===3 ? 'opacity-0' : 'text-green-400'">▲</div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- SCORE readout: real 7-segment LED digits -->
                        <div class="relative flex flex-col items-center gap-2 rounded-xl border border-cyan-400/50 bg-black p-3" role="status" aria-live="polite" aria-atomic="true">
                            <div class="game-point-bursts" aria-hidden="true">
                                <template x-for="burst in pointBursts" :key="burst.id">
                                    <span class="game-point-burst" :class="burst.delta > 0 ? 'text-green-300' : 'text-orange-300'"
                                          x-text="(burst.delta > 0 ? '+' : '') + burst.delta"></span>
                                </template>
                            </div>
                            <div class="text-xs font-bold tracking-widest text-cyan-200">JOUW PUNTEN</div>
                            <span class="sr-only" x-text="score + ' punten'"></span>
                            <div aria-hidden="true" class="flex px-3 py-2 rounded-lg bg-black border-2 border-slate-600 shadow-inner">
                                <template x-for="(d, di) in scoreDigits" :key="'sd'+di">
                                    <div class="digit-7seg">
                                        <template x-for="seg in SEG_LIST" :key="seg">
                                            <div class="seg" :class="'seg-'+seg+' '+(segOn(d,seg)?'':'off')"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs font-bold" x-show="scoreChange !== null && scoreChange !== 0"
                               :class="scoreChange > 0 ? 'text-green-300' : 'text-orange-300'"
                               x-text="(scoreChange > 0 ? '+' : '') + scoreChange + ' punten'"></p>
                            <p class="text-[10px] text-gray-300"><span x-text="spinCost"></span> punten per spin · Goed antwoord: +10 tot +50</p>
                        </div>
                    </div>

                    <!-- BLOK 3: LED KRANS + MESSAGEBOARD -->
                    <!-- One 9x3 grid. Tile indexes run clockwise: top 0-8, right 9, bottom 10-18 (shown right->left), left 19. -->
                    <div class="border-x-2 border-indigo-400 bg-[#141428] p-2">
                        <div class="led-frame" :class="ledActive ? 'led-chasing' : ''">
                            <div class="led-grid">
                                <template x-for="i in [0,1,2,3,4,5,6,7,8]" :key="'ledtop'+i">
                                    <div class="led-tile" :class="ledTileClass(i)">
                                        <img :src="ledIconUrl(i)" class="led-tile-icon" alt="">
                                    </div>
                                </template>

                                <div class="led-tile" :class="ledTileClass(19)">
                                    <img :src="ledIconUrl(19)" class="led-tile-icon" alt="">
                                </div>
                                <div class="led-message" :class="message.length > 16 ? 'led-message--long' : ''">
                                    <span x-text="message"></span>
                                </div>
                                <div class="led-tile" :class="ledTileClass(9)">
                                    <img :src="ledIconUrl(9)" class="led-tile-icon" alt="">
                                </div>

                                <template x-for="i in [18,17,16,15,14,13,12,11,10]" :key="'ledbot'+i">
                                    <div class="led-tile" :class="ledTileClass(i)">
                                        <img :src="ledIconUrl(i)" class="led-tile-icon" alt="">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- BLOK 2: REELS + displays -->
                    <div class="rounded-b-2xl border-x-2 border-b-2 border-indigo-400 bg-gradient-to-b from-[#0d0d1f] to-black p-2">
                        <div class="flex items-center gap-1.5">
                            <div class="flex flex-col items-center gap-1 w-10">
                                <button type="button" class="arcade-btn bg-emerald-600 text-white text-[8px] px-1 py-1.5 w-full" @click="cycleLevel()" :disabled="!canChangeLevel()">LVL</button>
                                <template x-for="n in [3,2,1]" :key="'lvl'+n">
                                    <div class="font-bold leading-none transition text-lg"
                                         :class="level === n ? 'text-yellow-300 neon-glow' : 'text-gray-700'" x-text="n"></div>
                                </template>
                            </div>

                            <div class="flex-1 flex justify-center gap-1.5">
                                <template x-for="(reel, i) in reels" :key="'reelbox'+i">
                                    <div class="reel-window flex-1 aspect-square rounded-lg border-4 transition"
                                         :class="[reel.spinning ? 'border-cyan-400 is-spinning' : (reel.held ? 'border-green-400' : 'border-fuchsia-600'), { 'reel-match': !spinning && hasReelResult && matchType !== 'none' && reels.filter(other => other.items[0] === reel.items[0]).length > 1 }]">
                                        <div class="reel-strip"
                                             :style="'transform:translateY(' + reel.offset + 'px); transition:' + (reel.transitionMs ? ('transform ' + reel.transitionMs + 'ms cubic-bezier(0.22,1.35,0.36,1)') : 'none') + ';'">
                                            <template x-for="(itemId, idx) in reel.items" :key="'item'+idx">
                                                <div class="reel-item aspect-square">
                                                    <img :src="categoryIconUrl(itemId)" class="w-4/5 h-4/5 object-contain">
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex flex-col items-center gap-1 w-10">
                                <button type="button" class="arcade-btn bg-red-600 text-white text-[8px] px-1 py-1.5 w-full" @click="pressStop()" :disabled="!stopEnabled()">STOP</button>
                                <template x-for="p in [50,40,30,20,10]" :key="'pt'+p">
                                    <div class="font-bold leading-none transition text-xs"
                                         :class="Number(lastPointsLabel) === p ? 'text-yellow-300 neon-glow' : 'text-gray-700'" x-text="p"></div>
                                </template>
                            </div>
                        </div>

                        <!-- REJECT / SKIP -->
                        <div class="flex justify-center gap-2 my-2">
                            <button type="button" class="arcade-btn px-4 py-1.5 rounded-full bg-orange-600 text-white text-[10px] font-bold" @click="reject()" :disabled="!hasQuestion">REJECT</button>
                            <button type="button" class="arcade-btn px-4 py-1.5 rounded-full bg-sky-600 text-white text-[10px] font-bold" @click="skip()" :disabled="!hasQuestion">SKIP</button>
                        </div>

                        <!-- MENU / HOLD / SPIN -->
                        <div class="flex justify-center gap-1.5">
                            <button type="button" @click="$dispatch('toggle-site-menu')" class="arcade-btn flex-1 py-3 bg-stone-300 text-black text-[10px] font-bold">MENU</button>
                            <template x-for="(reel, i) in reels" :key="'hold'+i">
                                <button type="button" class="arcade-btn flex-1 py-3 text-[10px] font-bold"
                                        :class="reel.held ? 'bg-green-600 text-white' : 'bg-stone-300 text-black'"
                                        @click="toggleHold(i)" :disabled="reel.held ? !canUnhold(i) : !canHold(i)">
                                    <span x-text="reel.held ? 'HELD' : 'HOLD'"></span>
                                </button>
                            </template>
                            <button type="button" class="arcade-btn flex-1 py-3 bg-green-600 text-white text-[10px] font-bold disabled:opacity-40 disabled:cursor-not-allowed" @click="spin()" :disabled="!canSpin()">
                                <span x-text="spinning ? '...' : 'SPIN'"></span>
                                <span class="block text-[9px]" x-text="'-' + spinCost + ' pnt'"></span>
                            </button>
                        </div>
                        <div x-show="score < spinCost && !hasQuestion && !spinning && !isWon" class="mt-3 rounded-lg border border-orange-400/50 bg-orange-950 p-3 text-center text-[10px] leading-relaxed">
                            <p>Te weinig punten voor een spin. Kies HOLD bij een vrije rol en beantwoord een vraag om punten te verdienen.</p>
                            <button type="button" class="mt-2 rounded bg-orange-600 px-3 py-2 font-bold" @click="location.reload()">Nieuw spel</button>
                        </div>
                        <p x-show="reels.every((reel) => reel.held) && !isWon" class="mt-2 text-center text-[10px] text-cyan-200">Klik op HELD om een rol vrij te maken voor je volgende spin.</p>
                    </div>

                    <!-- QUESTION MODAL -->
                    <div x-show="question" x-cloak class="fixed inset-0 z-30 flex items-center justify-center bg-black/85 p-4">
                        <div class="bg-[#1a1a2e] border-2 border-fuchsia-600 rounded-xl p-5 max-w-md w-full text-xs max-h-[85vh] overflow-y-auto">
                            <div class="text-yellow-300 mb-2" x-show="isThemeQuestion">🎯 THEME QUESTION <span x-text="themeSlot"></span>/3</div>
                            <div class="text-cyan-300 mb-1" x-text="question?.category_name ?? question?.theme_name"></div>
                            <div class="mb-3 flex flex-wrap justify-between gap-2 text-xs">
                                <span class="text-green-300" x-show="!feedback">Goed antwoord: <strong x-text="'+' + questionPoints + ' punten'"></strong></span>
                                <span class="text-cyan-200">Jouw punten: <strong x-text="score"></strong></span>
                            </div>
                            <div class="mb-3" x-text="question?.text"></div>

                            <div x-show="question?.image_path" class="mb-3 h-32 bg-gray-800 border border-gray-600 rounded flex items-center justify-center text-[9px] text-gray-400">
                                [afbeelding]
                            </div>

                            <template x-if="feedback">
                                <div class="game-answer-feedback mb-3 p-2 rounded" :class="feedback.correct ? 'bg-green-900/50 text-green-300' : 'bg-red-900/50 text-red-300'">
                                    <div x-text="feedback.correct ? '✓ Correct!' : '✗ Helaas'"></div>
                                    <div class="mt-1 text-gray-200" x-text="feedback.text"></div>
                                    <div class="mt-2 text-sm font-bold" x-text="feedback.correct ? '+' + feedback.points + ' punten verdiend!' : 'Geen punten erbij.'"></div>
                                </div>
                            </template>

                            <template x-if="!feedback">
                            <div>
                                {{-- DEV-CHEAT (tijdelijk, weghalen) --}}
                                <div x-show="devHint()" class="mb-2 rounded border border-dashed border-yellow-400/60 px-2 py-1 text-yellow-300">⭐ DEV: <span x-text="devHint()"></span></div>
                                <template x-if="['mc_1goed','waar_niet','foto','blitz','zoom'].includes(question?.type)">
                                    <div class="flex flex-col gap-2">
                                        <template x-for="opt in question.options" :key="opt.key">
                                            <button type="button" class="px-3 py-2 rounded border text-left"
                                                    :class="draft === opt.key ? 'bg-gray-500 border-gray-400' : 'border-gray-600 hover:border-cyan-400'"
                                                    @click="draft = opt.key; submit()">
                                                <span x-text="opt.text"></span><span class="text-yellow-300" x-text="devMark(opt.key)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="['mc_2goed','mc_3goed'].includes(question?.type)">
                                    <div class="flex flex-col gap-2">
                                        <template x-for="opt in question.options" :key="opt.key">
                                            <button type="button" class="px-3 py-2 rounded border text-left"
                                                    :class="(draft||[]).includes(opt.key) ? 'bg-gray-500 border-gray-400' : 'border-gray-600 hover:border-cyan-400'"
                                                    @click="toggleMulti(opt.key)">
                                                <span x-text="opt.text"></span><span class="text-yellow-300" x-text="devMark(opt.key)"></span>
                                            </button>
                                        </template>
                                        <button type="button" class="mt-1 px-3 py-2 rounded bg-fuchsia-600" @click="submit()">Bevestig</button>
                                    </div>
                                </template>

                                <template x-if="question?.type === 'volgorde'">
                                    <div>
                                        <div class="flex flex-wrap gap-1 mb-2">
                                            <template x-for="(k,idx) in (draft||[])" :key="'ord'+idx">
                                                <span class="px-2 py-1 rounded bg-cyan-700 text-[10px]" x-text="(idx+1)+'. '+optionText(k)"></span>
                                            </template>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <template x-for="opt in question.options" :key="opt.key">
                                                <button type="button" class="px-3 py-2 rounded border text-left"
                                                        :class="(draft||[]).includes(opt.key) ? 'opacity-30 border-gray-700' : 'border-gray-600 hover:border-cyan-400'"
                                                        :disabled="(draft||[]).includes(opt.key)"
                                                        @click="addToOrder(opt.key)">
                                                    <span x-text="opt.text"></span><span class="text-yellow-300" x-text="devMark(opt.key)"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <button type="button" class="mt-2 px-3 py-2 rounded bg-fuchsia-600 disabled:opacity-30"
                                                :disabled="(draft||[]).length !== question.options.length" @click="submit()">Bevestig volgorde</button>
                                    </div>
                                </template>

                                <template x-if="['matching','sleep'].includes(question?.type)">
                                    <div>
                                        <div class="flex gap-3 mb-2">
                                            <div class="flex-1 flex flex-col gap-1">
                                                <template x-for="opt in question.options.left" :key="'l'+opt.key">
                                                    <button type="button" class="px-2 py-1 rounded border text-left text-[10px]"
                                                            :class="pairClass('from', opt.key)"
                                                            @click="pickPair('from', opt.key)">
                                                        <span x-text="opt.text"></span><span class="text-yellow-300" x-text="devMark(opt.key, 'from')"></span>
                                                    </button>
                                                </template>
                                            </div>
                                            <div class="flex-1 flex flex-col gap-1">
                                                <template x-for="opt in question.options.right" :key="'r'+opt.key">
                                                    <button type="button" class="px-2 py-1 rounded border text-left text-[10px]"
                                                            :class="pairClass('to', opt.key)"
                                                            @click="pickPair('to', opt.key)">
                                                        <span x-text="opt.text"></span><span class="text-yellow-300" x-text="devMark(opt.key, 'to')"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="text-[9px] text-gray-400 mb-2">Gekoppeld: <span x-text="(draft||[]).length"></span> / <span x-text="question.options.left.length"></span></div>
                                        <button type="button" class="px-3 py-2 rounded bg-fuchsia-600 disabled:opacity-30"
                                                :disabled="(draft||[]).length !== question.options.left.length" @click="submit()">Bevestig</button>
                                    </div>
                                </template>

                                <template x-if="question?.type === 'schatting'">
                                    <div class="flex gap-2">
                                        <input type="number" x-model="draft" class="flex-1 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white">
                                        <button type="button" class="px-3 py-2 rounded bg-fuchsia-600" @click="submit()">OK</button>
                                    </div>
                                </template>

                                <template x-if="question?.type === 'invulzin'">
                                    <div class="flex gap-2">
                                        <input type="text" x-model="draft" class="flex-1 bg-gray-800 border border-gray-600 rounded px-2 py-1 text-white">
                                        <button type="button" class="px-3 py-2 rounded bg-fuchsia-600" @click="submit()">OK</button>
                                    </div>
                                </template>

                                <template x-if="question?.type === 'hotspot'">
                                    <div class="relative h-40 bg-gray-800 border border-gray-600 rounded cursor-crosshair"
                                         @click="clickHotspot($event)">
                                        <div class="absolute inset-0 flex items-center justify-center text-[9px] text-gray-500">klik in de afbeelding</div>
                                        <div x-show="devHotspotPolygon()" class="absolute inset-0 bg-yellow-300/50 pointer-events-none" :style="'clip-path:' + devHotspotPolygon()"></div>
                                    </div>
                                </template>
                            </div>
                            </template>
                        </div>
                    </div>

                    <!-- WIN MODAL -->
                    <div x-show="isWon" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/90 p-4">
                        <div class="border-4 border-yellow-400 rounded-2xl p-8 text-center max-w-sm neon-box text-yellow-300">
                            <div class="text-3xl mb-2">🏆 YOU WON! 🏆</div>
                            <div class="mb-4">Final score: <span x-text="score"></span></div>
                            <button type="button" class="px-4 py-2 rounded bg-fuchsia-600 text-white" @click="location.reload()">Speel opnieuw</button>
                        </div>
                    </div>
                </div>
                </template>
            </div>
        </div>
    </div>
</x-bare-layout>
