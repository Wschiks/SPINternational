// The 20-segment LED krans bonus wheel that plays after a correct answer.
export function ledKransMixin(ledSegments) {
    return {
        ledSegments,
        ledActive: false,
        ledIndex: 0,
        ledTimer: null,

        // `resumeQueue` is called once the player presses STOP, so any
        // follow-up queued after this one (e.g. a theme unlock) still runs.
        runLedKrans(resumeQueue) {
            this.say('led');
            this.ledActive = true;
            this.ledIndex = 0;
            this.pendingFollowUp = resumeQueue;
            this.ledTimer = setInterval(() => {
                this.ledIndex = (this.ledIndex + 1) % this.ledSegments.length;
            }, 80);
            return 'async';
        },
    };
}
