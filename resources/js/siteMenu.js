const SETTINGS = {
    animationsEnabled: { key: 'spin.settings.animations', defaultValue: true },
    buttonHintsEnabled: { key: 'spin.settings.buttonHints', defaultValue: true },
    highContrastEnabled: { key: 'spin.settings.highContrast', defaultValue: false },
    autoTutorialEnabled: { key: 'spin.settings.autoTutorial', defaultValue: true },
};

function readSetting(setting) {
    try {
        const storedValue = window.localStorage.getItem(setting.key);

        return storedValue === null ? setting.defaultValue : storedValue === 'true';
    } catch {
        return setting.defaultValue;
    }
}

function storeSetting(setting, value) {
    try {
        window.localStorage.setItem(setting.key, String(value));
    } catch {
        // The setting still applies for this page when storage is unavailable.
    }
}

export function createSiteMenu() {
    return {
        menuOpen: false,
        activePanel: 'main',
        sessionId: null,
        currentScore: null,
        scoreHistory: [],
        animationsEnabled: readSetting(SETTINGS.animationsEnabled),
        buttonHintsEnabled: readSetting(SETTINGS.buttonHintsEnabled),
        highContrastEnabled: readSetting(SETTINGS.highContrastEnabled),
        autoTutorialEnabled: readSetting(SETTINGS.autoTutorialEnabled),

        init() {
            this.applySettings();
        },

        toggleMenu(gameState = {}) {
            this.updateGameState(gameState);

            if (this.menuOpen) {
                this.closeMenu();

                return;
            }

            this.menuOpen = true;
            this.activePanel = 'main';
            this.$nextTick(() => this.$refs.menuPanel?.focus());
        },

        closeMenu(returnFocus = true) {
            this.menuOpen = false;
            this.activePanel = 'main';

            if (returnFocus) {
                this.$nextTick(() => this.$refs.menuButton?.focus());
            }
        },

        showPanel(panel) {
            this.activePanel = panel;
            this.$nextTick(() => this.$refs.panelHeading?.focus());
        },

        updateGameState(gameState = {}) {
            if (gameState.sessionId) {
                this.sessionId = gameState.sessionId;
            }

            if (Number.isFinite(gameState.score)) {
                this.currentScore = gameState.score;
            }

            if (Array.isArray(gameState.scoreHistory)) {
                this.scoreHistory = gameState.scoreHistory;
            }
        },

        startTutorial() {
            this.closeMenu(false);
            window.dispatchEvent(new CustomEvent('start-game-tutorial'));
        },

        toggleSetting(name) {
            if (!Object.hasOwn(SETTINGS, name)) {
                return;
            }

            this[name] = !this[name];
            storeSetting(SETTINGS[name], this[name]);
            this.applySettings();
        },

        applySettings() {
            document.documentElement.classList.toggle('game-user-reduced-motion', !this.animationsEnabled);
            document.documentElement.classList.toggle('game-button-tooltips-disabled', !this.buttonHintsEnabled);
            document.documentElement.classList.toggle('game-high-contrast', this.highContrastEnabled);

            window.dispatchEvent(new CustomEvent('game-settings-changed', {
                detail: {
                    animationsEnabled: this.animationsEnabled,
                    buttonHintsEnabled: this.buttonHintsEnabled,
                    highContrastEnabled: this.highContrastEnabled,
                    autoTutorialEnabled: this.autoTutorialEnabled,
                },
            }));
        },

        historyLabel(eventType) {
            return {
                starting_points: 'Startpunten',
                spin_cost: 'Spin',
                question_correct: 'Goed antwoord',
                penalty_skip: 'Vraag overgeslagen',
                penalty_reject: 'Vraag afgewezen',
                led_bonus: 'LED-bonus',
                theme_correct: 'Themavraag goed',
                theme_completion_bonus: 'Thema voltooid',
                vertical_bonus: 'Verticale bonus',
            }[eventType] ?? 'Score aangepast';
        },

        formatEventTime(timestamp) {
            const date = new Date(timestamp);

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return new Intl.DateTimeFormat('nl-NL', {
                hour: '2-digit',
                minute: '2-digit',
            }).format(date);
        },
    };
}
