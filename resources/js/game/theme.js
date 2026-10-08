// Erasmus+ theme icons in the crown, and the spin that picks one of them
// after a badgeboard row is completed: the row's middle question mark cycles
// through the 4 theme icons and lands on the theme the server picked.
export function themeMixin(themes) {
    return {
        themeList: Object.entries(themes).map(([id, t]) => ({ id: Number(id), ...t })),
        themes: {
            1: { active: false, checks: 0 },
            2: { active: false, checks: 0 },
            3: { active: false, checks: 0 },
            4: { active: false, checks: 0 },
        },

        themeSpinActive: false,
        // Badgeboard row whose middle cell shows a theme instead of the
        // question mark, and the theme it currently shows. Both stay set
        // until that theme's question is closed.
        themeSpinRow: null,
        themeSpinId: null,

        // `bonus` is the answer's horizontal_bonus: { row, theme_id, slot, question, points }.
        // `resumeQueue` runs once the theme question is on screen.
        runThemeSpin(bonus, resumeQueue) {
            const ids = this.themeList.map((t) => t.id);
            const totalSteps = ids.length * 3 + ids.indexOf(bonus.theme_id) + 1;
            let step = 0;

            this.say('theme');
            this.themeSpinActive = true;
            this.themeSpinRow = bonus.row;

            const tick = () => {
                this.themeSpinId = ids[step % ids.length];
                step++;
                if (step < totalSteps) {
                    // Starts fast and slows down towards the chosen theme.
                    setTimeout(tick, 70 + 330 * (step / totalSteps) ** 2);
                    return;
                }
                setTimeout(() => {
                    this.themeSpinActive = false;
                    this.openQuestion(bonus.question, true, bonus.slot, bonus.points);
                    resumeQueue();
                }, 800);
            };
            tick();

            return 'async';
        },

        endThemeRound() {
            this.themeSpinRow = null;
            this.themeSpinId = null;
        },

        themeImgClasses(id) {
            const active = this.themes[id]?.active;
            const picked = this.themeSpinRow !== null && this.themeSpinId === id;
            return [active || picked ? 'opacity-100' : 'opacity-30 grayscale', picked ? 'pulse-glow' : ''].join(' ');
        },

        themeImgStyle(id) {
            return this.themes[id]?.active ? 'filter: drop-shadow(0 0 8px currentColor);' : '';
        },
    };
}
