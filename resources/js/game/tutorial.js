const GAMEPLAY_STEPS = [
    {
        target: 'menu',
        title: 'Jouw spelmenu',
        text: 'Open hier op elk moment de spelregels, scorehistorie en instellingen.',
    },
    {
        target: 'score',
        title: 'Let op je punten',
        text: 'Een spin kost punten. Goede antwoorden en bonussen vullen je score weer aan.',
    },
    {
        target: 'reels',
        title: 'Draai de rollen',
        text: 'De drie rollen bepalen welke categorie en hoeveel punten je kunt verdienen.',
    },
    {
        target: 'hold',
        title: 'Houd een rol vast',
        text: 'Kies HOLD om een symbool te bewaren en beantwoord de bijbehorende vraag.',
    },
    {
        target: 'spin',
        title: 'Klaar om te spinnen',
        text: 'Druk op SPIN wanneer er minstens één rol vrij is en je genoeg punten hebt.',
    },
];

export function tutorialMixin() {
    let highlightedElement;
    let repositionTutorial;

    return {
        tutorialActive: false,
        tutorialIndex: 0,
        tutorialSteps: GAMEPLAY_STEPS,
        tutorialPosition: '',

        initTutorial() {
            repositionTutorial = () => this.positionTutorial();
            window.addEventListener('resize', repositionTutorial);
            window.addEventListener('scroll', repositionTutorial, true);
        },

        destroyTutorial() {
            this.removeTutorialHighlight();
            window.removeEventListener('resize', repositionTutorial);
            window.removeEventListener('scroll', repositionTutorial, true);
        },

        get tutorialStep() {
            return this.tutorialSteps[this.tutorialIndex] ?? this.tutorialSteps[0];
        },

        startTutorial() {
            if (!this.started) {
                this.message = 'Start eerst het spel; open daarna MENU om de rondleiding te beginnen.';

                return;
            }

            this.tutorialActive = true;
            this.tutorialIndex = 0;
            this.$nextTick(() => this.positionTutorial());
        },

        maybeStartTutorial() {
            let autoTutorialEnabled = true;
            let tutorialSeen = false;

            try {
                autoTutorialEnabled = window.localStorage.getItem('spin.settings.autoTutorial') !== 'false';
                tutorialSeen = window.localStorage.getItem('spin.tutorial.seen') === 'true';
            } catch {
                // Storage is optional; keep the default first-game tutorial behavior.
            }

            if (autoTutorialEnabled && !tutorialSeen) {
                setTimeout(() => this.startTutorial(), 350);
            }
        },

        nextTutorialStep() {
            if (this.tutorialIndex >= this.tutorialSteps.length - 1) {
                this.finishTutorial();

                return;
            }

            this.tutorialIndex += 1;
            this.$nextTick(() => this.positionTutorial());
        },

        previousTutorialStep() {
            if (this.tutorialIndex === 0) {
                return;
            }

            this.tutorialIndex -= 1;
            this.$nextTick(() => this.positionTutorial());
        },

        finishTutorial() {
            this.tutorialActive = false;
            this.removeTutorialHighlight();

            try {
                window.localStorage.setItem('spin.tutorial.seen', 'true');
            } catch {
                // Finishing the tutorial must not depend on browser storage.
            }
        },

        positionTutorial() {
            if (!this.tutorialActive) {
                return;
            }

            this.removeTutorialHighlight();
            const target = document.querySelector(`[data-tutorial-target="${this.tutorialStep.target}"]`);

            if (!target) {
                this.nextTutorialStep();

                return;
            }

            highlightedElement = target;
            highlightedElement.classList.add('game-tutorial-highlight');
            highlightedElement.scrollIntoView({ block: 'nearest', inline: 'nearest' });

            window.requestAnimationFrame(() => {
                const rect = highlightedElement.getBoundingClientRect();
                const width = Math.min(320, window.innerWidth - 32);
                const left = Math.max(16, Math.min(rect.left + (rect.width / 2) - (width / 2), window.innerWidth - width - 16));
                const tooltipHeight = 190;
                const below = rect.bottom + 12;
                const top = below + tooltipHeight <= window.innerHeight
                    ? below
                    : Math.max(16, rect.top - tooltipHeight - 12);

                this.tutorialPosition = `left:${left}px;top:${top}px;width:${width}px`;
            });
        },

        removeTutorialHighlight() {
            highlightedElement?.classList.remove('game-tutorial-highlight');
            highlightedElement = null;
        },
    };
}
