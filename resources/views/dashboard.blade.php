<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Slot Machine
        </h2>
    </x-slot>

    <div class="py-12" x-data="slotMachine()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">

                <!-- Reels -->
                <div class="flex justify-center gap-8 mb-6">
                    <template x-for="(reel, index) in reels" :key="index">
                        <div class="flex flex-col items-center gap-4">

                            <div class="w-40 h-40 md:w-48 md:h-48 bg-gray-100 border-8 rounded-2xl flex items-center justify-center text-8xl md:text-9xl transition"
                                 :class="reel.spinning ? 'animate-pulse border-indigo-300' : (reel.held ? 'border-green-400 bg-green-50' : 'border-gray-300')">
                                <span x-text="reel.symbol"></span>
                            </div>

                            <button
                                @click="hold(index)"
                                :disabled="reel.held || isSpinning"
                                class="w-full px-4 py-2 rounded-lg font-semibold text-sm transition"
                                :class="reel.held
                                    ? 'bg-green-500 text-white cursor-not-allowed'
                                    : 'bg-yellow-400 text-gray-900 hover:bg-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed'"
                            >
                                <span x-text="reel.held ? 'HELD' : 'Hold'"></span>
                            </button>

                        </div>
                    </template>
                </div>

                <!-- Controls -->
                <div class="flex justify-center gap-4 mt-10">
                    <button
                        @click="spin()"
                        :disabled="isSpinning || allHeld"
                        class="px-10 py-4 bg-indigo-600 text-black font-bold text-xl hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        <span x-text="isSpinning ? 'Rolling...' : 'Spin!'"></span>
                    </button>

                    <button
                        @click="reset()"
                        class="px-10 py-4 bg-red-500 text-black rounded-lg font-bold text-xl hover:bg-red-600 transition"
                    >
                        Reset
                    </button>
                </div>

                <p class="mt-8 text-2xl font-bold" x-show="result" x-text="result"></p>
                <p class="mt-4 text-sm text-gray-500" x-show="allHeld" x-text="'All reels held — resetting...'"></p>

            </div>
        </div>
    </div>

    <script>
        function slotMachine() {
            return {
                symbols: [
                    '😀', '😳', '🤬', '😎', '🤯', '🥳', '😭', '🤢',
                    '👻', '🤡', '💀', '👽', '🔥', '💎', '⭐', '🍀'
                ],
                reels: [
                    { symbol: '😀', spinning: false, held: false },
                    { symbol: '😀', spinning: false, held: false },
                    { symbol: '😀', spinning: false, held: false },
                ],
                isSpinning: false,
                result: '',

                get allHeld() {
                    return this.reels.every(r => r.held);
                },

                hold(index) {
                    if (this.isSpinning) return;
                    this.reels[index].held = true;

                    if (this.allHeld) {
                        setTimeout(() => this.reset(), 800);
                    }
                },

                spin() {
                    if (this.isSpinning || this.allHeld) return;

                    this.isSpinning = true;
                    this.result = '';

                    const intervals = [];
                    let lastIndex = -1;

                    this.reels.forEach((reel, i) => {
                        if (reel.held) return;

                        reel.spinning = true;
                        lastIndex = i;

                        intervals[i] = setInterval(() => {
                            reel.symbol = this.symbols[Math.floor(Math.random() * this.symbols.length)];
                        }, 80);
                    });

                    this.reels.forEach((reel, i) => {
                        if (reel.held) return;

                        setTimeout(() => {
                            clearInterval(intervals[i]);
                            reel.symbol = this.symbols[Math.floor(Math.random() * this.symbols.length)];
                            reel.spinning = false;

                            if (i === lastIndex) {
                                this.checkResult();
                                this.isSpinning = false;
                            }
                        }, 1000 + i * 600);
                    });
                },

                checkResult() {
                    const values = this.reels.map(r => r.symbol);
                    if (values[0] === values[1] && values[1] === values[2]) {
                        this.result = `🎉 Jackpot! Triple ${values[0]}`;
                    } else {
                        this.result = 'No match, try again!';
                    }
                },

                reset() {
                    this.isSpinning = false;
                    this.result = '';
                    this.reels.forEach(reel => {
                        reel.symbol = '😀';
                        reel.spinning = false;
                        reel.held = false;
                    });
                }
            }
        }
    </script>
</x-app-layout>
