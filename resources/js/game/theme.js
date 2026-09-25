// Erasmus+ theme icons in the crown: resuming, and the blink-to-choose flow
// after a horizontal badgeboard bonus.
export function themeMixin(themes) {
    return {
        themeList: Object.entries(themes).map(([id, t]) => ({ id: Number(id), ...t })),
        themes: {
            1: { active: false, checks: 0 },
            2: { active: false, checks: 0 },
            3: { active: false, checks: 0 },
            4: { active: false, checks: 0 },
        },
        themeCredits: 0,

        themeBlinkActive: false,
        themeBlinkList: [],
        themeBlinkIndex: 0,
        themeBlinkTimer: null,

        onThemeIconClick(themeId) {
            const t = this.themes[themeId];
            if (!t || !t.active || t.checks >= 3) return;
            if (this.hasQuestion || this.spinning || this.ledActive || this.themeBlinkActive) return;
            this.resumeTheme(themeId);
        },

        async resumeTheme(themeId) {
            try {
                const data = await this.api('POST', `/game/${this.sessionId}/theme/select`, { theme_id: themeId });
                this.applyState(data.state);
                this.openQuestion(data.question, true, data.slot, data.points);
            } catch (e) {}
        },

        // `resumeQueue` is called once the player presses STOP to pick a
        // theme, so any follow-up queued after this one still runs.
        runThemeBlink(availableThemes, resumeQueue) {
            const list = availableThemes.filter((t) => !t.already_active).map((t) => t.id);
            if (!list.length) return;
            this.say('theme');
            this.themeBlinkList = list;
            this.themeBlinkIndex = 0;
            this.themeBlinkActive = true;
            this.pendingFollowUp = resumeQueue;
            this.themeBlinkTimer = setInterval(() => {
                this.themeBlinkIndex = (this.themeBlinkIndex + 1) % this.themeBlinkList.length;
            }, 350);
            return 'async';
        },

        themeImgClasses(id) {
            const active = this.themes[id]?.active;
            const blinking = this.themeBlinkActive && this.themeBlinkList[this.themeBlinkIndex] === id;
            return [active ? 'opacity-100' : 'opacity-30 grayscale', blinking ? 'pulse-glow' : ''].join(' ');
        },

        themeImgStyle(id) {
            return this.themes[id]?.active ? 'filter: drop-shadow(0 0 8px currentColor);' : '';
        },
    };
}
