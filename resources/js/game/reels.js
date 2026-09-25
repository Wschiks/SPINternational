// The 3 reels: spinning animation, hold/unhold, and match-based points.
export function reelsMixin() {
    return {
        reels: [
            { items: [null], offset: 0, transitionMs: 0, spinning: false, held: false },
            { items: [null], offset: 0, transitionMs: 0, spinning: false, held: false },
            { items: [null], offset: 0, transitionMs: 0, spinning: false, held: false },
        ],
        hasReelResult: false,
        heldReelIndex: null,
        matchType: null,
        spinning: false,

        canSpin() {
            return this.started && !this.isWon && this.score >= this.spinCost && this.reels.some((reel) => !reel.held)
                && !this.spinning && !this.hasQuestion && !this.ledActive && !this.themeBlinkActive;
        },
        canHold(i) {
            return this.started && !this.spinning && !this.hasQuestion && this.hasReelResult && !this.reels[i].held;
        },
        canUnhold(i) {
            return this.started && !this.spinning && !this.hasQuestion && !this.ledActive && !this.themeBlinkActive && this.reels[i].held;
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
            const durations = [1300, 1650, 2000]; // ms — staggered stop, reel 1 lands first

            // Held reels stay put — no strip animation for those.
            this.reels.forEach((reel, i) => {
                if (reel.held) { reel.spinning = false; return; }
                const finalId = data.reels[i];
                const strip = [];
                for (let k = 0; k < 14; k++) strip.push(1 + Math.floor(Math.random() * 16));
                strip.push(finalId);
                reel.items = strip;
                reel.offset = 0;
                reel.transitionMs = 0;
                reel.spinning = true;
            });

            await this.$nextTick();

            const windows = this.$root.querySelectorAll('.reel-window');
            this.reels.forEach((reel, i) => {
                if (reel.held) return;
                const itemH = windows[i] ? windows[i].clientWidth : 100;
                requestAnimationFrame(() => {
                    reel.transitionMs = durations[i];
                    reel.offset = -(reel.items.length - 1) * itemH;
                });

                setTimeout(() => {
                    reel.spinning = false;
                    reel.transitionMs = 0;
                    reel.items = [data.reels[i]];
                    reel.offset = 0;
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
