// The 20-segment LED krans bonus wheel that plays after a correct answer.
export function ledKransMixin(ledSegments) {
    return {
        ledSegments,
        ledActive: false,
        ledIndex: 0,
        ledTimer: null,
        // During the win show after STOP: the set of tile indexes that are
        // lit in the current frame (null when no show is playing).
        ledFlash: null,

        // Tile background colour comes from the segment colour; the lit tile
        // and the one it just left (trail) get extra classes so the light
        // visibly travels around the krans.
        ledTileClass(i) {
            const classes = [`led-tile--${this.ledSegments[i]?.color ?? 'red'}`];
            if (this.ledFlash) {
                if (this.ledFlash.has(i)) classes.push(i === this.ledIndex ? 'led-tile--lit' : 'led-tile--flash');
            } else if (this.ledActive) {
                const len = this.ledSegments.length;
                if (this.ledIndex === i) classes.push('led-tile--lit');
                else if ((this.ledIndex - 1 + len) % len === i) classes.push('led-tile--trail');
            }
            return classes.join(' ');
        },

        // `resumeQueue` is called once the player presses STOP, so any
        // follow-up queued after this one (e.g. a theme unlock) still runs.
        runLedKrans(resumeQueue) {
            this.say('led');
            this.ledActive = true;
            this.ledIndex = 0;
            this.pendingFollowUp = resumeQueue;
            this.ledTimer = setInterval(() => {
                this.ledIndex = (this.ledIndex + 1) % this.ledSegments.length;
            }, 70);
            return 'async';
        },

        // Win show after STOP: odd/even tiles alternate, the whole krans
        // flashes, then only the tile the player stopped on blinks.
        // `ledActive` stays true throughout so the rest of the UI stays locked.
        async playLedWin(winIndex) {
            const all = this.ledSegments.map((_, i) => i);
            const odd = all.filter((i) => i % 2 === 1);
            const even = all.filter((i) => i % 2 === 0);
            const frames = [
                ...Array(4).fill([[odd, 140], [even, 140]]).flat(),
                ...Array(3).fill([[all, 140], [[], 110]]).flat(),
                ...Array(4).fill([[[winIndex], 170], [[], 110]]).flat(),
                [[winIndex], 500],
            ];
            for (const [lit, ms] of frames) {
                this.ledFlash = new Set(lit);
                await new Promise((resolve) => setTimeout(resolve, ms));
            }
            this.ledFlash = null;
        },
    };
}
