import { SEGMENTS, SEG_LIST } from './segments';
import { apiMixin } from './api';
import { assetsMixin } from './assets';
import { reelsMixin } from './reels';
import { themeMixin } from './theme';
import { ledKransMixin } from './ledkrans';
import { questionMixin } from './question';
import { effectsMixin } from './effects';
import { tutorialMixin } from './tutorial';
import { progressMixin } from './progress';

const MESSAGES = {
    gameplay: ['Give it a spin', 'Nice try', 'Keep on going', 'Well done', 'Congratulations'],
    led: ['Push, push', 'Go for it!', 'Extra bonus'],
    badge: ['3 in a row!', 'Vertical bonus!', 'Horizontal complete!'],
    theme: ['Theme unlocked!', '1 down, 3 to go!', 'Almost there!'],
    win: ['YOU WON!'],
};

/**
 * The Alpine component for the whole game screen (registered as `gameApp`
 * in app.js). Shared/cross-cutting state (score, message, session id) lives
 * here; everything else is composed in from the game/*.js mixins so each
 * feature area is a separate, independently-ownable file:
 *
 *   reels.js     — spinning, holding, unholding
 *   theme.js     — crown theme icons + the row-complete theme spin
 *   ledkrans.js  — the 20-segment bonus wheel
 *   question.js  — the answer modal for every question type
 *   effects.js   — point-change popups + celebrations
 *   assets.js    — image URL helpers
 *   api.js       — fetch + CSRF wrapper
 */
export function createGameApp(categories, themes, ledKrans, assetPaths, pointRules) {
    return {
        categories,
        SEG_LIST,

        started: false,
        startLevel: 1,
        sessionId: null,
        level: 1,
        score: 0,
        spinCost: pointRules.spinCost,
        scoreChange: null,
        scoreHistory: [],
        lastPointsLabel: '0',
        message: 'Give it a spin',
        isWon: false,

        badge: { icon_states: {}, verticals: {}, horizontals: {} },

        // Set by a mixin's "async" step (led krans / theme blink) so pressStop
        // can resume whatever follow-up was queued after it.
        pendingFollowUp: null,

        ...apiMixin(),
        ...assetsMixin(assetPaths),
        ...reelsMixin(),
        ...themeMixin(themes),
        ...ledKransMixin(ledKrans),
        ...questionMixin(),
        ...effectsMixin(),
        ...tutorialMixin(),
        ...progressMixin(),

        init() {
            this.initEffects();
            this.initTutorial();

            const gameStart = this.savedProgress?.sessionId
                ? this.resumeSavedGame()
                : this.startGame();

            Promise.resolve(gameStart).then(() => {
                if (!this.started) {
                    this.startGame();
                }
            });
        },

        destroy() {
            this.destroyEffects();
            this.destroyTutorial();
            clearInterval(this.ledTimer);
            clearInterval(this.themeBlinkTimer);
        },

        get boardMessage() {
            return this.reels.every((reel) => reel.held) && !this.isWon
                ? 'Alle rollen vast! Druk op SPIN'
                : this.message;
        },

        // The numbers light up as in the design until a question has a value.
        pointsLightClass(points) {
            const current = Number(this.lastPointsLabel);
            if (![50, 40, 30, 20, 10].includes(current)) return '';
            return current === points ? 'is-lit' : 'is-dim';
        },

        get scoreDigits() {
            return String(this.score).padStart(5, '0').split('').map(Number);
        },
        segOn(digit, seg) {
            const idx = SEG_LIST.indexOf(seg);
            return !!(SEGMENTS[digit] && SEGMENTS[digit][idx]);
        },

        applyState(state) {
            this.scoreChange = this.sessionId === state.session_id ? state.current_score - this.score : null;
            this.sessionId = state.session_id;
            this.level = state.level_selected;
            this.score = state.current_score;
            this.scoreHistory = state.score_history ?? [];
            this.spinCost = state.spin_cost;
            this.badge = state.badgeboard;
            this.themes = state.themes;
            this.themeCredits = state.theme_credits;
            this.isWon = state.is_won;
            this.showPointChange(this.scoreChange);
            this.saveProgress();

            if (typeof window !== 'undefined') {
                window.dispatchEvent(new CustomEvent('game-state-updated', {
                    detail: {
                        sessionId: this.sessionId,
                        score: this.score,
                        scoreHistory: this.scoreHistory,
                    },
                }));
            }
        },

        async startGame() {
            const state = await this.api('POST', '/game/start', { level: this.startLevel });
            this.applyState(state);
            this.started = true;
            this.message = `Je start met ${this.score} punten. Kies SPIN!`;
            this.$nextTick(() => this.maybeStartTutorial());
        },

        say(category) {
            const lines = MESSAGES[category] ?? ['...'];
            this.message = lines[Math.floor(Math.random() * lines.length)];
        },

        canChangeLevel() {
            return !this.hasQuestion && !this.spinning && !this.ledActive && !this.themeBlinkActive;
        },
        stopEnabled() {
            return this.ledActive || this.themeBlinkActive;
        },

        cycleLevel() {
            if (!this.canChangeLevel()) return;
            const next = this.level >= 3 ? 1 : this.level + 1;
            this.api('POST', `/game/${this.sessionId}/level`, { level: next }).then((state) => this.applyState(state));
        },

        // STOP is dual-purpose: it claims the LED krans bonus, or (if a theme
        // is blinking instead) confirms the currently-lit theme.
        async pressStop() {
            if (this.ledActive) {
                clearInterval(this.ledTimer);
                this.ledActive = false;
                const idx = this.ledIndex;
                const data = await this.api('POST', `/game/${this.sessionId}/led-krans/stop`, { index: idx });
                this.applyState(data.state);
                this.lastPointsLabel = String(data.points);
                this.message = `+${data.points} bonus!`;
                this.celebrate('bonus');
                this.resumeFollowUps();
                return;
            }
            if (this.themeBlinkActive) {
                clearInterval(this.themeBlinkTimer);
                this.themeBlinkActive = false;
                const themeId = this.themeBlinkList[this.themeBlinkIndex];
                try {
                    const data = await this.api('POST', `/game/${this.sessionId}/theme/select`, { theme_id: themeId });
                    this.applyState(data.state);
                    this.openQuestion(data.question, true, data.slot, data.points);
                } catch (e) {}
                this.resumeFollowUps();
            }
        },

        resumeFollowUps() {
            const next = this.pendingFollowUp;
            this.pendingFollowUp = null;
            next?.();
        },

    };
}
