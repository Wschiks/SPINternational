const COLORS = ['#67e8f9', '#f0abfc', '#fde047', '#86efac'];

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
            const count = kind === 'win' ? 40 : 20;
            this.confetti = Array.from({ length: count }, (_, index) => ({
                id: ++nextEffectId,
                style: `left:${(index * 37) % 100}%;--drift:${(index % 2 ? 1 : -1) * (20 + index % 5 * 15)}px;--delay:${index % 6 * 55}ms;--color:${COLORS[index % COLORS.length]};--turn:${180 + index * 47}deg`,
            }));
            celebrationTimer = setTimeout(() => {
                this.confetti = [];
                this.celebration = null;
            }, 2400);
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
