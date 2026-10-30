const STORAGE_KEY = 'spin.game.progress';

function localStorageDriver() {
    return globalThis.localStorage ?? null;
}

function readSavedProgress() {
    try {
        const savedProgress = JSON.parse(localStorageDriver()?.getItem(STORAGE_KEY) ?? 'null');

        return Number.isInteger(savedProgress?.sessionId) ? savedProgress : null;
    } catch {
        return null;
    }
}

export function progressMixin() {
    return {
        savedProgress: readSavedProgress(),
        progressLoading: false,

        saveProgress(showConfirmation = false) {
            if (!this.sessionId) {
                return;
            }

            const savedProgress = {
                sessionId: this.sessionId,
                score: this.score,
                level: this.level,
                savedAt: new Date().toISOString(),
            };

            try {
                localStorageDriver()?.setItem(STORAGE_KEY, JSON.stringify(savedProgress));
                this.savedProgress = savedProgress;

                if (showConfirmation) {
                    this.message = 'Voortgang lokaal opgeslagen';
                }
            } catch {
                if (showConfirmation) {
                    this.message = 'Lokaal opslaan is niet beschikbaar in deze browser.';
                }
            }
        },

        discardSavedProgress() {
            try {
                localStorageDriver()?.removeItem(STORAGE_KEY);
            } catch {
                // A new game can still start when browser storage is unavailable.
            }

            this.savedProgress = null;
        },

        async resumeSavedGame() {
            if (!this.savedProgress?.sessionId || this.progressLoading) {
                return;
            }

            this.progressLoading = true;

            try {
                const state = await this.api('GET', `/game/${this.savedProgress.sessionId}/state`);
                this.restoreProgressState(state);
                this.message = 'Opgeslagen spel hervat';
            } catch (error) {
                this.message = error.message || 'Het opgeslagen spel kon niet worden hervat.';
            } finally {
                this.progressLoading = false;
            }
        },

        restoreProgressState(state) {
            this.applyState(state);
            this.started = true;
            this.matchType = state.match_type ?? null;

            const reelResults = Array.isArray(state.reels) ? state.reels : [null, null, null];
            const heldReels = new Set(state.held_reels ?? []);
            const usedReels = new Set(state.used_reels ?? []);

            this.reels = reelResults.map((categoryId, index) => ({
                items: [categoryId],
                offset: 0,
                transitionMs: 0,
                spinning: false,
                held: heldReels.has(index + 1),
                used: usedReels.has(index + 1),
            }));
            this.hasReelResult = reelResults.some((categoryId) => categoryId !== null);
            this.heldReelIndex = state.current_reel_index ? state.current_reel_index - 1 : null;

            if (state.active_question) {
                this.openQuestion(
                    state.active_question.question,
                    state.active_question.kind === 'theme',
                    state.active_question.slot,
                    state.active_question.points,
                );
            }

            if (state.led_krans_pending && !state.active_question) {
                this.$nextTick(() => this.runLedKrans(() => {}));
            }
        },
    };
}
