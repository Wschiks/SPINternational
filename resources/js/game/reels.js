// The 3 reels: spinning animation, hold/unhold, and match-based points.
export function reelsMixin() {
    return {
        // Each reel shows three icons (items[1] is the one on the line); `offset`
        // counts items and is what the strip animates. Starts as in the design.
        reels: [
            { items: [1, 3, 2], offset: 1, transitionMs: 0, spinning: false, held: false, used: false },
            { items: [6, 2, 12], offset: 1, transitionMs: 0, spinning: false, held: false, used: false },
            { items: [5, 4, 1], offset: 1, transitionMs: 0, spinning: false, held: false, used: false },
        ],
        hasReelResult: false,
        heldReelIndex: null,
        matchType: null,
        spinning: false,

        canSpin() {
            return this.started && !this.isWon && this.score >= this.spinCost
                &&!this.spinning && !this.hasQuestion && !this.ledActive && !this.themeSpinActive;
        },
        canHold(i) {
            return this.started && !this.spinning && !this.hasQuestion && this.hasReelResult && !this.reels[i].held && !this.reels[i].used;
        },
        canUnhold(i) {
            return this.started && !this.spinning && !this.hasQuestion && !this.ledActive && !this.themeSpinActive && this.reels[i].held;
        },

        async spin() {
            if (!this.canSpin()) return;
            this.spinning = true;

            let data;
            try {
                data = await this.api('POST', `/game/${this.sessionId}/spin`, {});
                this.applyState(data);
                this.message = `Spin: -${this.spinCost} punten`;
            } catch (error) {
                this.spinning = false;
                this.message = error.message || 'Spinnen is niet gelukt. Probeer opnieuw.';
                return;
            }
            const durations = this.reducedMotion ? [0, 0, 0] : [1300, 1650, 2000];

            // All 3 reels held: the server released them, so all of them spin.
            if (this.reels.every((reel) => reel.held)) {
                this.reels.forEach((reel) => { reel.held = false; });
            }

            // A new spin makes every reel holdable again.
            this.reels.forEach((reel) => { reel.used = false; });

            const randomIcon = () => 1 + Math.floor(Math.random() * 16);
            const landed = data.reels.map((finalId) => [randomIcon(), finalId, randomIcon()]);

            // Held reels stay put — no strip animation for those. The strip starts
            // with what is on screen and ends with what lands.
            this.reels.forEach((reel, i) => {
                if (reel.held) { reel.spinning = false; return; }
                const spinning = Array.from({ length: 12 }, randomIcon);
                reel.items = [...reel.items, ...spinning, ...landed[i]];
                reel.offset = 1;
                reel.transitionMs = 0;
                reel.spinning = true;
            });

            await this.$nextTick();

            this.reels.forEach((reel, i) => {
                if (reel.held) return;
                requestAnimationFrame(() => {
                    reel.transitionMs = durations[i];
                    reel.offset = reel.items.length - 2;
                });

                setTimeout(() => {
                    reel.spinning = false;
                    reel.transitionMs = 0;
                    reel.items = landed[i];
                    reel.offset = 1;
                }, durations[i] + 40);
            });

            setTimeout(() => {
                this.spinning = false;
                this.hasReelResult = true;
                this.matchType = data.match_type;
                this.lastPointsLabel = String(this.previewPoints(data.reels));
                this.message = 'Kies HOLD en verdien punten met een goed antwoord!';
            }, Math.max(...durations) + 80);
        },

        // One item is 130/170 of the window's width; the line is 45.88% of it down.
        reelStripStyle(reel) {
            const transition = reel.transitionMs ? `transform ${reel.transitionMs}ms cubic-bezier(0.22,1.35,0.36,1)` : 'none';
            return `transform:translateY(calc(45.88cqw - ${reel.offset} * 76.47cqw));transition:${transition};`;
        },

        previewPoints(finalReels) {
            const counts = {};
            finalReels.forEach((c) => (counts[c] = (counts[c] || 0) + 1));
            const max = Math.max(...Object.values(counts));
            if (max >= 3) return 50;
            if (max === 2) return 40;
            return this.level * 10;
        },

        async hold(i) {
            if (!this.canHold(i)) return;
            try {
                const data = await this.api('POST', `/game/${this.sessionId}/hold`, { reel: i + 1 });
                this.applyState(data.state);
                this.heldReelIndex = i;
                this.openQuestion(data.question, false, null, data.points);
            } catch (e) {}
        },

        async unhold(i) {
            if (!this.canUnhold(i)) return;
            try {
                const data = await this.api('POST', `/game/${this.sessionId}/unhold`, { reel: i + 1 });
                this.applyState(data);
                this.reels[i].held = false;
            } catch (e) {}
        },

        toggleHold(i) {
            if (this.reels[i].held) {
                this.unhold(i);
            } else {
                this.hold(i);
            }
        },
    };
}
