const COLORS = ['#67e8f9', '#f0abfc', '#fde047', '#86efac', '#fb7185', '#fb923c', '#60a5fa', '#ffffff'];
const EMOJI = ['⭐', '🎉', '✨', '🪙', '💎', '🎊', '🍀', '🔥'];
const COUNTS = { correct: 150, bonus: 200, win: 350 };
const CELEBRATION_MS = 3800;
const rand = (min, max) => min + Math.random() * (max - min);

export function effectsMixin() {
    let motionPreference;
    let motionListener;
    let userAnimationsEnabled = true;
    let nextEffectId = 0;
    let celebrationTimer;
    const pointTimers = new Set();

    return {
        reducedMotion: false,
        pointBursts: [],
        confetti: [],
        celebration: null,
        flashId: 0,

        initEffects() {
            motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
            userAnimationsEnabled = window.localStorage?.getItem('spin.settings.animations') !== 'false';
            this.reducedMotion = motionPreference.matches || !userAnimationsEnabled;
            motionListener = (event) => {
                this.reducedMotion = event.matches || !userAnimationsEnabled;
                if (this.reducedMotion) this.clearEffects();
            };
            motionPreference.addEventListener('change', motionListener);
        },

        setUserMotionPreference(enabled) {
            userAnimationsEnabled = enabled;
            this.reducedMotion = (motionPreference?.matches ?? false) || !enabled;

            if (this.reducedMotion) {
                this.clearEffects();
            }
        },

        showPointChange(delta) {
            if (!delta || this.reducedMotion) return;
            const id = ++nextEffectId;
            this.pointBursts = [...this.pointBursts.slice(-2), { id, delta }];
            const timer = setTimeout(() => {
                this.pointBursts = this.pointBursts.filter((burst) => burst.id !== id);
                pointTimers.delete(timer);
            }, 1400);
            pointTimers.add(timer);
        },

        celebrate(kind = 'correct') {
            if (this.reducedMotion) return;
            clearTimeout(celebrationTimer);
            this.celebration = kind;
            this.flashId = ++nextEffectId;
            const count = COUNTS[kind] ?? COUNTS.correct;
            this.confetti = Array.from({ length: count }, (_, index) => {
                const cannon = index % 3 === 0;
                const fromLeft = index % 2 === 0;
                const vars = [
                    `--color:${COLORS[index % COLORS.length]}`,
                    `--size:${rand(8, 20)}px`,
                    `--turn:${rand(360, 1440)}deg`,
                    `--delay:${rand(0, cannon ? 150 : 900)}ms`,
                    `--round:${index % 4 === 0 ? '50%' : '2px'}`,
                ];
                if (cannon) {
                    vars.push(
                        `left:${fromLeft ? 0 : 100}%`,
                        `--dx:${(fromLeft ? 1 : -1) * rand(15, 90)}cqw`,
                        `--dy:${-rand(70, 150)}cqw`,
                    );
                } else {
                    vars.push(`left:${rand(0, 100)}%`, `--drift:${rand(-120, 120)}px`, `--fall:${rand(1800, 3200)}ms`);
                }
                return {
                    id: ++nextEffectId,
                    cannon,
                    emoji: index % 9 === 0 ? EMOJI[index % EMOJI.length] : '',
                    style: vars.join(';'),
                };
            });
            celebrationTimer = setTimeout(() => {
                this.confetti = [];
                this.celebration = null;
            }, CELEBRATION_MS);
        },

        clearEffects() {
            clearTimeout(celebrationTimer);
            pointTimers.forEach((timer) => clearTimeout(timer));
            pointTimers.clear();
            this.pointBursts = [];
            this.confetti = [];
            this.celebration = null;
        },

        destroyEffects() {
            this.clearEffects();
            motionPreference?.removeEventListener('change', motionListener);
        },
    };
}
