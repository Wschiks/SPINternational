// The question modal: opening it, building the answer draft for every
// question type, submitting it, and the follow-up effects (LED bonus,
// theme unlock, win) that a correct/wrong answer can trigger.
export function questionMixin() {
    return {
        hasQuestion: false,
        isThemeQuestion: false,
        themeSlot: null,
        question: null,
        draft: null,
        feedback: null,
        pairPending: null,

        openQuestion(q, isTheme, slot) {
            this.question = q;
            this.isThemeQuestion = isTheme;
            this.themeSlot = slot;
            this.hasQuestion = true;
            this.feedback = null;
            this.draft = ['mc_2goed', 'mc_3goed', 'volgorde', 'matching', 'sleep'].includes(q.type) ? [] : null;
        },

        optionText(key) {
            const opt = (this.question?.options || []).find((o) => o.key === key);
            return opt ? opt.text : key;
        },

        toggleMulti(key) {
            this.draft = this.draft || [];
            const idx = this.draft.indexOf(key);
            if (idx === -1) this.draft.push(key);
            else this.draft.splice(idx, 1);
        },

        addToOrder(key) {
            this.draft = this.draft || [];
            this.draft.push(key);
        },

        pickPair(side, key) {
            if (side === 'from') {
                this.pairPending = key;
                return;
            }
            if (!this.pairPending) return;
            this.draft = this.draft || [];
            this.draft = this.draft.filter((p) => p.from !== this.pairPending);
            this.draft.push({ from: this.pairPending, to: key });
            this.pairPending = null;
        },

        pairClass(side, key) {
            const paired = (this.draft || []).some((p) => p[side] === key);
            if (paired) return 'bg-gray-500 border-gray-400';
            if (side === 'from' && this.pairPending === key) return 'border-cyan-400';
            return 'border-gray-600 hover:border-cyan-400';
        },

        clickHotspot(evt) {
            const rect = evt.currentTarget.getBoundingClientRect();
            const x = ((evt.clientX - rect.left) / rect.width) * 100;
            const y = ((evt.clientY - rect.top) / rect.height) * 100;
            this.draft = { x, y };
            this.submit();
        },

        async submit() {
            if (this.draft === null || this.draft === undefined) return;
            const path = this.isThemeQuestion
                ? `/game/${this.sessionId}/theme-answer`
                : `/game/${this.sessionId}/answer`;
            const data = await this.api('POST', path, { question_id: this.question.id, answer: this.draft });
            this.feedback = { correct: data.correct, text: data.feedback };
            this.applyState(data.state);

            if (data.correct) {
                this.lastPointsLabel = String(data.points_awarded);
            }
            this.say('gameplay');

            setTimeout(() => this.closeQuestionAndFollowUp(data), data.correct ? 1200 : 2000);
        },

        closeQuestionAndFollowUp(data) {
            if (!this.isThemeQuestion && this.heldReelIndex !== null && data.correct) {
                this.reels[this.heldReelIndex].held = true;
            }
            this.heldReelIndex = null;
            this.hasQuestion = false;
            this.question = null;
            this.feedback = null;
            this.draft = null;
            this.pairPending = null;

            const followUps = [];
            if (data.trigger_led_krans) followUps.push((done) => this.runLedKrans(done));
            if (data.horizontal_bonus?.available_themes) {
                const opts = data.horizontal_bonus.available_themes;
                followUps.push((done) => this.runThemeBlink(opts, done));
            }
            if (data.vertical_bonus) this.say('badge');
            if (data.theme_complete) this.say('theme');
            if (data.game_won) {
                followUps.push(() => {
                    this.isWon = true;
                    this.say('win');
                });
            }

            this.runQueue(followUps);
        },

        // Runs each follow-up in order; a follow-up returning 'async' pauses
        // the queue until something else (e.g. pressStop) resumes it.
        runQueue(fns) {
            if (!fns.length) return;
            const [first, ...rest] = fns;
            const done = () => this.runQueue(rest);
            const result = first(done);
            if (result !== 'async') done();
        },
    };
}
