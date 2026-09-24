import { SEGMENTS, SEG_LIST } from './segments';
import { apiMixin } from './api';
import { assetsMixin } from './assets';
import { reelsMixin } from './reels';
import { themeMixin } from './theme';
import { ledKransMixin } from './ledkrans';
import { questionMixin } from './question';

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
 *   theme.js     — crown theme icons + the blink-to-choose flow
 *   ledkrans.js  — the 20-segment bonus wheel
 *   question.js  — the answer modal for every question type
 *   assets.js    — image URL helpers
 *   api.js       — fetch + CSRF wrapper
 */
export function createGameApp(categories, themes, ledKrans, assetPaths) {
    return {
        categories,
        SEG_LIST,

        started: false,
        startLevel: 1,
        sessionId: null,
        level: 1,
        score: 0,
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

        init() {},

        get scoreDigits() {
            return String(this.score).padStart(5, '0').split('').map(Number);
        },
        segOn(digit, seg) {
            const idx = SEG_LIST.indexOf(seg);
            return !!(SEGMENTS[digit] && SEGMENTS[digit][idx]);
        },

        applyState(state) {
            this.sessionId = state.session_id;
            this.level = state.level_selected;
            this.score = state.current_score;
            this.badge = state.badgeboard;
            this.themes = state.themes;
            this.themeCredits = state.theme_credits;
            this.isWon = state.is_won;
        },

        async startGame() {
            const state = await this.api('POST', '/game/start', { level: this.startLevel });
            this.applyState(state);
            this.started = true;
            this.say('gameplay');
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
                    this.openQuestion(data.question, true, data.slot);
                } catch (e) {}
                this.resumeFollowUps();
            }
        },

        resumeFollowUps() {
            const next = this.pendingFollowUp;
            this.pendingFollowUp = null;
            next?.();
        },

        async skip() {
            if (!this.hasQuestion || this.isThemeQuestion) return;
            const data = await this.api('POST', `/game/${this.sessionId}/skip`, {});
            this.applyState(data.state);
            this.openQuestion(data.question, false, null);
            this.lastPointsLabel = '-20';
            this.message = 'Skipped';
        },

        async reject() {
            if (!this.hasQuestion || this.isThemeQuestion) return;
            const data = await this.api('POST', `/game/${this.sessionId}/reject`, {});
            this.applyState(data.state);
            this.hasQuestion = false;
            this.question = null;
            this.draft = null;
            this.heldReelIndex = null;
            this.lastPointsLabel = '-10';
            this.message = 'Rejected';
        },
    };
}
