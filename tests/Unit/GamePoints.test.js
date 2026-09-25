import assert from 'node:assert/strict';
import test from 'node:test';
import { reelsMixin } from '../../resources/js/game/reels.js';
import { questionMixin } from '../../resources/js/game/question.js';

function playableReels() {
    return { ...reelsMixin(), started: true, score: 100, spinCost: 10, sessionId: 1 };
}

test('spinning requires enough points and a free reel', () => {
    const game = playableReels();
    game.score = 9;
    assert.equal(game.canSpin(), false);
    game.score = 10;
    assert.equal(game.canSpin(), true);
    game.reels.forEach((reel) => { reel.held = true; });
    assert.equal(game.canSpin(), false);
});

test('the charged balance appears before the reels finish animating', async (context) => {
    context.mock.method(globalThis, 'setTimeout', () => 0);
    const previousAnimationFrame = globalThis.requestAnimationFrame;
    globalThis.requestAnimationFrame = (callback) => callback();
    context.after(() => {
        if (previousAnimationFrame) globalThis.requestAnimationFrame = previousAnimationFrame;
        else delete globalThis.requestAnimationFrame;
    });
    const game = playableReels();
    game.api = async () => ({ current_score: 90, reels: [1, 2, 3], match_type: 'none' });
    game.applyState = (state) => { game.score = state.current_score; };
    game.$nextTick = async () => {};
    game.$root = { querySelectorAll: () => [] };

    await game.spin();

    assert.equal(game.score, 90);
    assert.equal(game.message, 'Spin: -10 punten');
});

test('a failed spin resets the button without changing the displayed balance', async () => {
    const game = playableReels();
    game.api = async () => { throw new Error('Niet genoeg punten.'); };

    await game.spin();

    assert.equal(game.spinning, false);
    assert.equal(game.score, 100);
    assert.equal(game.message, 'Niet genoeg punten.');
});

test('a question shows its reward and correct-answer feedback includes the awarded points', async (context) => {
    context.mock.method(globalThis, 'setTimeout', () => 0);
    const game = { ...questionMixin(), sessionId: 1, score: 90 };
    game.openQuestion({ id: 7, type: 'mc_1goed' }, false, null, 40);
    assert.equal(game.questionPoints, 40);
    game.draft = 'a';
    game.api = async () => ({ correct: true, points_awarded: 40, feedback: 'Goed!', state: { current_score: 130 } });
    game.applyState = (state) => { game.score = state.current_score; };

    await game.submit();

    assert.equal(game.score, 130);
    assert.equal(game.feedback.points, 40);
    assert.equal(game.message, 'Goed antwoord! +40 punten');
});

test('an answer cannot be sent twice while the first request is pending', async (context) => {
    context.mock.method(globalThis, 'setTimeout', () => 0);
    const game = { ...questionMixin(), sessionId: 1 };
    game.openQuestion({ id: 7, type: 'mc_1goed' }, false, null, 10);
    game.draft = 'a';
    let finishAnswer;
    let requests = 0;
    game.api = () => {
        requests++;
        return new Promise((resolve) => { finishAnswer = resolve; });
    };
    game.applyState = () => {};

    const firstAnswer = game.submit();
    await game.submit();
    finishAnswer({ correct: true, points_awarded: 10, feedback: 'Goed!', state: {} });
    await firstAnswer;

    assert.equal(requests, 1);
    assert.equal(game.submitting, false);
});
