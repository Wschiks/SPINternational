import assert from 'node:assert/strict';
import test from 'node:test';
import { reelsMixin } from '../../resources/js/game/reels.js';
import { questionMixin } from '../../resources/js/game/question.js';
import { themeMixin } from '../../resources/js/game/theme.js';
import { assetsMixin } from '../../resources/js/game/assets.js';
import { effectsMixin } from '../../resources/js/game/effects.js';
import { progressMixin } from '../../resources/js/game/progress.js';

function playableReels() {
    return { ...reelsMixin(), started: true, score: 100, spinCost: 10, sessionId: 1 };
}

test('spinning requires enough points, even with every reel held', () => {
    const game = playableReels();
    game.score = 9;
    assert.equal(game.canSpin(), false);
    game.score = 10;
    assert.equal(game.canSpin(), true);
    game.reels.forEach((reel) => { reel.held = true; });
    assert.equal(game.canSpin(), true);
});

test('a reel cannot be held again after its question, even when answered wrong', () => {
    const game = { ...playableReels(), ...questionMixin(), hasReelResult: true, runLedKrans: () => {} };
    game.heldReelIndex = 0;
    game.closeQuestionAndFollowUp({ correct: false });

    assert.equal(game.reels[0].held, false);
    assert.equal(game.canHold(0), false);
    assert.equal(game.canHold(1), true);
});

test('spinning with every reel held releases them all', async (context) => {
    context.mock.method(globalThis, 'setTimeout', () => 0);
    const previousAnimationFrame = globalThis.requestAnimationFrame;
    globalThis.requestAnimationFrame = (callback) => callback();
    context.after(() => {
        if (previousAnimationFrame) globalThis.requestAnimationFrame = previousAnimationFrame;
        else delete globalThis.requestAnimationFrame;
    });
    const game = playableReels();
    game.reels.forEach((reel) => { reel.held = true; });
    game.api = async () => ({ current_score: 90, reels: [1, 2, 3], match_type: 'none' });
    game.applyState = (state) => { game.score = state.current_score; };
    game.$nextTick = async () => {};
    game.$root = { querySelectorAll: () => [] };

    await game.spin();

    assert.ok(game.reels.every((reel) => !reel.held && !reel.used && reel.spinning));
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
    const game = { ...questionMixin(), ...effectsMixin(), sessionId: 1, score: 90 };
    game.openQuestion({ id: 7, type: 'mc_1goed' }, false, null, 40);
    assert.equal(game.questionPoints, 40);
    game.draft = 'a';
    game.api = async () => ({ correct: true, points_awarded: 40, feedback: 'Goed!', state: { current_score: 130 } });
    game.applyState = (state) => { game.score = state.current_score; };

    await game.submit();

    assert.equal(game.score, 130);
    assert.equal(game.feedback.points, 40);
    assert.equal(game.message, 'Goed antwoord! +40 punten');
    assert.equal(game.celebration, 'correct');
});

test('an answer cannot be sent twice while the first request is pending', async (context) => {
    context.mock.method(globalThis, 'setTimeout', () => 0);
    const game = { ...questionMixin(), ...effectsMixin(), sessionId: 1 };
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

test('skip sends one request and opens the replacement question', async () => {
    const game = { ...questionMixin(), sessionId: 4, lastPointsLabel: '0', message: '' };
    game.openQuestion({ id: 7, type: 'mc_1goed' }, false, null, 10);
    let finishSkip;
    let requests = 0;
    game.api = () => {
        requests++;
        return new Promise((resolve) => { finishSkip = resolve; });
    };
    game.applyState = () => {};

    const firstSkip = game.skip();
    await game.skip();
    finishSkip({ question: { id: 8, type: 'mc_1goed' }, points: 20, state: {} });
    await firstSkip;

    assert.equal(requests, 1);
    assert.equal(game.question.id, 8);
    assert.equal(game.questionPoints, 20);
    assert.equal(game.lastPointsLabel, '-20');
    assert.equal(game.message, 'Vraag overgeslagen: -20 punten');
});

test('reject closes the active question after the server accepts it', async () => {
    const game = { ...questionMixin(), sessionId: 4, heldReelIndex: 1, lastPointsLabel: '0', message: '' };
    game.openQuestion({ id: 7, type: 'mc_1goed' }, false, null, 10);
    game.api = async () => ({ state: {} });
    game.applyState = () => {};

    await game.reject();

    assert.equal(game.hasQuestion, false);
    assert.equal(game.question, null);
    assert.equal(game.heldReelIndex, null);
    assert.equal(game.lastPointsLabel, '-10');
    assert.equal(game.message, 'Vraag afgewezen: -10 punten');
});

test('saved progress restores reels and an active matching question', async (context) => {
    const previousLocalStorage = globalThis.localStorage;
    const storedValues = new Map([['spin.game.progress', JSON.stringify({ sessionId: 12, score: 80, level: 2 })]]);
    globalThis.localStorage = {
        getItem: (key) => storedValues.get(key) ?? null,
        setItem: (key, value) => storedValues.set(key, value),
        removeItem: (key) => storedValues.delete(key),
    };
    context.after(() => {
        if (previousLocalStorage) globalThis.localStorage = previousLocalStorage;
        else delete globalThis.localStorage;
    });

    const game = {
        ...reelsMixin(),
        ...questionMixin(),
        ...progressMixin(),
        started: false,
        sessionId: null,
        score: 0,
        level: 1,
        message: '',
    };
    game.api = async (method, path) => {
        assert.equal(method, 'GET');
        assert.equal(path, '/game/12/state');

        return {
            session_id: 12,
            current_score: 80,
            level_selected: 2,
            reels: [7, 7, 3],
            held_reels: [1],
            used_reels: [2],
            match_type: 'double',
            current_reel_index: 2,
            active_question: {
                kind: 'regular',
                question: { id: 9, type: 'matching', options: { left: [], right: [] } },
                points: 40,
                slot: null,
            },
            led_krans_pending: false,
        };
    };
    game.applyState = (state) => {
        game.sessionId = state.session_id;
        game.score = state.current_score;
        game.level = state.level_selected;
    };

    await game.resumeSavedGame();

    assert.equal(game.started, true);
    assert.equal(game.reels[0].held, true);
    assert.equal(game.reels[1].used, true);
    assert.equal(game.heldReelIndex, 1);
    assert.equal(game.hasReelResult, true);
    assert.equal(game.question.id, 9);
    assert.equal(game.questionPoints, 40);
    assert.equal(game.message, 'Opgeslagen spel hervat');
});

test('a completed row spins its question mark onto the chosen theme, then opens its question', (context) => {
    const timers = [];
    context.mock.method(globalThis, 'setTimeout', (fn) => { timers.push(fn); return 0; });
    const game = {
        ...themeMixin({ 1: {}, 2: {}, 3: {}, 4: {} }),
        ...questionMixin(),
        ...assetsMixin({ badgeboard: '/b', themes: { 1: '/t1', 2: '/t2', 3: '/t3', 4: '/t4' } }),
        say: () => {},
        badge: { horizontals: {} },
    };
    let resumed = false;
    const question = { id: 9, type: 'mc_1goed', options: [] };

    game.runThemeSpin({ row: 2, theme_id: 3, slot: 1, question, points: 50 }, () => { resumed = true; });
    assert.equal(game.themeSpinActive, true);
    while (timers.length) timers.shift()();

    assert.equal(game.badgeMiddleUrl(2), '/t3');
    assert.equal(game.badgeMiddleUrl(1), '/b/qmark.png');
    assert.equal(game.themeSpinActive, false);
    assert.equal(game.question, question);
    assert.equal(game.isThemeQuestion, true);
    assert.ok(resumed);

    game.reels = [];
    game.runLedKrans = () => {};
    game.closeQuestionAndFollowUp({ correct: false });
    assert.equal(game.badgeMiddleUrl(2), '/b/qmark.png');
});
