import assert from 'node:assert/strict';
import test from 'node:test';
import { effectsMixin } from '../../resources/js/game/effects.js';

test('point effects show gains and costs and disappear after their animation', (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const game = effectsMixin();

    game.showPointChange(0);
    game.showPointChange(null);
    assert.equal(game.pointBursts.length, 0);
    game.showPointChange(-10);
    game.showPointChange(40);

    assert.deepEqual(game.pointBursts.map((burst) => burst.delta), [-10, 40]);
    context.mock.timers.tick(1400);
    assert.deepEqual(game.pointBursts, []);
});

test('a new celebration replaces the old one and gets its full duration', (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const game = effectsMixin();
    game.celebrate('correct');
    const firstId = game.confetti[0].id;
    context.mock.timers.tick(1200);

    game.celebrate('win');

    assert.equal(game.celebration, 'win');
    assert.equal(game.confetti.length, 40);
    assert.notEqual(game.confetti[0].id, firstId);
    context.mock.timers.tick(1200);
    assert.equal(game.celebration, 'win');
    context.mock.timers.tick(1200);
    assert.equal(game.celebration, null);
    assert.deepEqual(game.confetti, []);
});

test('reduced motion suppresses decorative effects', () => {
    const game = effectsMixin();
    game.reducedMotion = true;

    game.showPointChange(50);
    game.celebrate('win');

    assert.deepEqual(game.pointBursts, []);
    assert.deepEqual(game.confetti, []);
    assert.equal(game.celebration, null);
});

test('changing the motion preference clears effects and cleanup removes the listener', (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const previousWindow = globalThis.window;
    let listener;
    let removedListener;
    globalThis.window = { matchMedia: () => ({
        matches: false,
        addEventListener: (event, callback) => { listener = callback; },
        removeEventListener: (event, callback) => { removedListener = callback; },
    }) };
    context.after(() => {
        if (previousWindow) globalThis.window = previousWindow;
        else delete globalThis.window;
    });
    const game = effectsMixin();
    game.initEffects();
    game.showPointChange(10);
    game.celebrate('bonus');

    listener({ matches: true });

    assert.equal(game.reducedMotion, true);
    assert.deepEqual(game.pointBursts, []);
    assert.deepEqual(game.confetti, []);
    listener({ matches: false });
    assert.equal(game.reducedMotion, false);
    game.destroyEffects();
    assert.equal(removedListener, listener);
    context.mock.timers.tick(3000);
    assert.deepEqual(game.confetti, []);
});
