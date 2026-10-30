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
    <div class="game-page"
         x-data='gameApp(@json($categories), @json($themes), @json($ledKrans), @json($assetPaths), { spinCost: @json($spinCost) })'
         @dev-show-answers.window="devShowAnswers = $event.detail">
        <div class="game-confetti-layer" aria-hidden="true">
            <template x-for="id in [flashId]" :key="id">
                <div class="game-flash" x-show="celebration !== null"></div>
            </template>
            <template x-for="piece in confetti" :key="piece.id">
        <span class="game-confetti-piece"
              :class="{ 'is-cannon': piece.cannon }"
              :style="piece.style"
              x-text="piece.emoji"></span>
            </template>
        </div>

        <!-- The machine: one picture of 1006 x 1656 from the design (TOTAAL SPIN-1START). Everything on it is placed
             in the design's own pixels with style="--x;--y;--w;--h" (see .st in app.css), so it scales as one piece. -->
        <div class="game-cabinet game-stage text-white spin-font" ...>
            <img src="{{ asset('images/game/stage/base.png') }}" class="game-stage-base" alt="" draggable="false">

            <div class="game-confetti-layer" aria-hidden="true">
                <template x-for="id in [flashId]" :key="id">
                    <div class="game-flash" x-show="celebration !== null"></div>
                </template>
                <template x-for="piece in confetti" :key="piece.id">
            <span class="game-confetti-piece"
                  :class="{ 'is-cannon': piece.cannon }"
                  :style="piece.style"
                  x-text="piece.emoji"></span>
                </template>
            </div>

            <template x-if="started">
                <div>
                    <!-- BLOK 6: KROON (the logo is part of the base picture) -->
                    @foreach ([[1, 62, 67, 171, 152, 100, 227], [3, 793, 69, 168, 150, 837, 229], [2, 35, 247, 210, 208, 96, 437], [4, 801, 276, 160, 161, 838, 437]] as [$theme, $x, $y, $w, $h, $checkX, $checkY])
                        <img :src="themeIconUrl({{ $theme }})" class="st" style="--x:{{ $x }};--y:{{ $y }};--w:{{ $w }};--h:{{ $h }}" :class="themeImgClasses({{ $theme }})" alt="">
                        @foreach ([1, 2, 3] as $check)
                            <img :src="checkboxUrl({{ $theme }}, {{ $check }})" class="st" style="--x:{{ $checkX + ($check - 1) * 31 }};--y:{{ $checkY }};--w:24;--h:23" alt="">
                        @endforeach
                    @endforeach

                    <!-- BLOK 5: BADGEBOARD -->
                    <div class="st badge-grid" style="--x:258;--y:404;--w:486;--h:334">
                        <template x-for="row in [1,2,3,4]" :key="'row'+row">
                            <template x-for="col in [1,2,3,4,5]" :key="'cell'+row+'-'+col">
                                <div class="badge-cell">
                                    <template x-if="col === 3">
                                        <img :src="badgeMiddleUrl(row)" :class="themeSpinRow === row ? 'pulse-glow' : ''" alt="">
                                    </template>
                                    <template x-if="col !== 3">
                                        <img :src="categoryIconUrl(categoryAt(row,col))" :class="badgeOn(row,col) ? 'badge-on' : 'badge-off'" alt="">
                                    </template>
                                </div>
                            </template>
                        </template>
                    </div>

                    <!-- BLOK 4: SCORE -->
                    <div class="st score-display" style="--x:419;--y:782;--w:170;--h:69" role="status" aria-live="polite" aria-atomic="true">
                        <div class="game-point-bursts" aria-hidden="true">
                            <template x-for="burst in pointBursts" :key="burst.id">
                                <span class="game-point-burst" :class="burst.delta > 0 ? 'text-green-300' : 'text-orange-300'"
                                      x-text="(burst.delta > 0 ? '+' : '') + burst.delta"></span>
                            </template>
                        </div>
                        <span class="sr-only" x-text="'Jouw punten: ' + score"></span>
                        <template x-for="(d, di) in scoreDigits" :key="'sd'+di">
                            <div class="digit-7seg" aria-hidden="true">
                                <template x-for="seg in SEG_LIST" :key="seg">
                                    <div class="seg" :class="'seg-'+seg+' '+(segOn(d,seg)?'':'off')"></div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- BLOK 3: LED KRANS + MESSAGEBOARD. Tile indexes run clockwise: top 0-8, right 9, bottom 10-18 (shown right->left), left 19. -->
                    <div class="st led-grid" style="--x:175;--y:875;--w:650;--h:196" :class="ledActive ? 'led-chasing' : ''">
                        <template x-for="i in [0,1,2,3,4,5,6,7,8]" :key="'ledtop'+i">
                            <div class="led-tile" :class="ledTileClass(i)" :style="ledTileStyle(i)">
                                <img :src="ledIconUrl(i)" class="led-tile-icon" alt="">
                            </div>
                        </template>

                        <div class="led-tile" :class="ledTileClass(19)" :style="ledTileStyle(19)">
                            <img :src="ledIconUrl(19)" class="led-tile-icon" alt="">
                        </div>
                        <div class="led-message" :class="boardMessage.length > 16 ? 'led-message--long' : ''">
                            <span x-text="boardMessage"></span>
                        </div>
                        <div class="led-tile" :class="ledTileClass(9)" :style="ledTileStyle(9)">
                            <img :src="ledIconUrl(9)" class="led-tile-icon" alt="">
                        </div>

                        <template x-for="i in [18,17,16,15,14,13,12,11,10]" :key="'ledbot'+i">
                            <div class="led-tile" :class="ledTileClass(i)" :style="ledTileStyle(i)">
                                <img :src="ledIconUrl(i)" class="led-tile-icon" alt="">
                            </div>
                        </template>
                    </div>

                    <!-- BLOK 2: REELS, under the design's frame -->
                    <template x-for="(reel, i) in reels" :key="'reelbox'+i">
                        <div class="st reel-window"
                             :style="'--x:' + [225, 421, 616][i] + ';--y:1138;--w:170;--h:286'"
                             :class="{ 'is-spinning': reel.spinning, 'is-held': reel.held, 'reel-match': !spinning && hasReelResult && matchType !== 'none' && reels.filter(other => other.items[1] === reel.items[1]).length > 1 }">
                            <div class="reel-strip" :style="reelStripStyle(reel)">
                                <template x-for="(itemId, idx) in reel.items" :key="'item'+idx">
                                    <div class="reel-item">
                                        <img :src="categoryIconUrl(itemId)" alt="">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <img src="{{ asset('images/game/stage/reel-outline.png') }}" class="st pointer-events-none" style="--x:186;--y:1108;--w:634;--h:344" alt="">

                    <!-- Level and what a right answer pays -->
                    @foreach ([[3, 96, 1151, 69, 91], [2, 40, 1231, 66, 96], [1, 112, 1284, 50, 98]] as [$n, $x, $y, $w, $h])
                        <img src="{{ asset('images/game/stage/level-'.$n.'.png') }}" class="st stage-light" style="--x:{{ $x }};--y:{{ $y }};--w:{{ $w }};--h:{{ $h }}" :class="level === {{ $n }} ? 'is-lit' : 'is-dim'" alt="">
                    @endforeach
                    @foreach ([[50, 834, 1130, 93, 51], [40, 852, 1185, 100, 49], [30, 861, 1240, 94, 49], [20, 851, 1295, 95, 50], [10, 839, 1350, 79, 50]] as [$points, $x, $y, $w, $h])
                        <img src="{{ asset('images/game/stage/points-'.$points.'.png') }}" class="st stage-light" style="--x:{{ $x }};--y:{{ $y }};--w:{{ $w }};--h:{{ $h }}" :class="pointsLightClass({{ $points }})" alt="">
                    @endforeach

                    <!-- Buttons -->
                    <x-game-button image="level" label="Level kiezen" :x="72" :y="1409" :w="114" :h="90" x-on:click="cycleLevel()" x-bind:disabled="!canChangeLevel()" />
                    <x-game-button image="stop" label="Stop" :x="821" :y="1409" :w="114" :h="90" x-on:click="pressStop()" x-bind:disabled="!stopEnabled()" />
                    <x-game-button image="reject" label="Reject" :x="365" :y="1454" :w="99" :h="63" x-on:click="reject()" x-bind:disabled="!hasQuestion || isThemeQuestion || !!feedback" />
                    <x-game-button image="skip" label="Skip" :x="542" :y="1462" :w="98" :h="54" x-on:click="skip()" x-bind:disabled="!hasQuestion || isThemeQuestion || !!feedback" />
                    <x-game-button image="menu" label="Menu" cap :x="39" :y="1518" :w="147" :h="109" x-on:click="$dispatch('toggle-site-menu')" />
                    @foreach ([255, 435, 615] as $reelIndex => $x)
                        <x-game-button image="hold" label="Hold {{ $reelIndex + 1 }}" cap :x="$x" :y="1525" :w="144" :h="97"
                                       x-on:click="toggleHold({{ $reelIndex }})"
                                       x-bind:class="reels[{{ $reelIndex }}].held ? 'is-held' : ''"
                                       x-bind:disabled="reels[{{ $reelIndex }}].held ? !canUnhold({{ $reelIndex }}) : !canHold({{ $reelIndex }})" />
                    @endforeach
                    <x-game-button image="spin" label="Spin" cap :x="782" :y="1499" :w="190" :h="127" x-on:click="spin()" x-bind:disabled="!canSpin()" />

                    <div x-show="score < spinCost && !hasQuestion && !spinning && !isWon" x-cloak class="st stage-note" style="--x:225;--y:1190;--w:561;--h:182">
                        <p>Te weinig punten voor een spin. Kies HOLD bij een vrije rol en beantwoord een vraag om punten te verdienen.</p>
                        <button type="button" class="stage-note-btn" @click="location.reload()">Nieuw spel</button>
                    </div>

                    <!-- QUESTION MODAL -->
                    <div x-show="question" x-cloak class="st z-30 flex items-center justify-center bg-black/85 p-4" style="--x:14;--y:15;--w:971;--h:1398">
                        <div class="bg-[#1a1a2e] border-2 border-fuchsia-600 rounded-xl p-5 w-full text-xs max-h-full overflow-y-auto">
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
                    <div x-show="isWon" x-cloak class="absolute inset-0 z-40 flex items-center justify-center bg-black/90 p-4">
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
</x-bare-layout>
