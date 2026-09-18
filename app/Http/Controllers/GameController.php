<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Services\Game\GameEngine;
use App\Support\GameCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class GameController extends Controller
{
    public function __construct(private GameEngine $engine) {}

    public function play()
    {
        return view('game.play', [
            'categories' => GameCatalog::CATEGORIES,
            'themes' => GameCatalog::THEMES,
            'ledKrans' => GameCatalog::LED_KRANS,
        ]);
    }

    public function start(Request $request)
    {
        $data = $request->validate(['level' => 'required|integer|min:1|max:3']);

        $session = $this->engine->start($request->user(), $data['level']);

        return response()->json($this->engine->state($session));
    }

    public function state(GameSession $session)
    {
        $this->authorizeSession($session);

        return response()->json($this->engine->state($session));
    }

    public function setLevel(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);
        $data = $request->validate(['level' => 'required|integer|min:1|max:3']);

        return $this->handle(function () use ($session, $data) {
            $this->engine->setLevel($session, $data['level']);

            return $this->engine->state($session);
        });
    }

    public function spin(GameSession $session)
    {
        $this->authorizeSession($session);

        return $this->handle(function () use ($session) {
            $this->engine->spin($session);

            return $this->engine->state($session);
        });
    }

    public function hold(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);
        $data = $request->validate(['reel' => 'required|integer|min:1|max:3']);

        return $this->handle(function () use ($session, $data) {
            $result = $this->engine->hold($session, $data['reel']);

            return array_merge($result, ['state' => $this->engine->state($session)]);
        });
    }

    public function unhold(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);
        $data = $request->validate(['reel' => 'required|integer|min:1|max:3']);

        return $this->handle(function () use ($session, $data) {
            $this->engine->unhold($session, $data['reel']);

            return $this->engine->state($session);
        });
    }

    public function answer(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);
        $data = $request->validate(['question_id' => 'required|integer', 'answer' => 'nullable']);

        return $this->handle(function () use ($session, $data) {
            $result = $this->engine->answer($session, $data['question_id'], $data['answer'] ?? null);

            return array_merge($result, ['state' => $this->engine->state($session)]);
        });
    }

    public function skip(GameSession $session)
    {
        $this->authorizeSession($session);

        return $this->handle(function () use ($session) {
            $result = $this->engine->skip($session);

            return array_merge($result, ['state' => $this->engine->state($session)]);
        });
    }

    public function reject(GameSession $session)
    {
        $this->authorizeSession($session);

        return $this->handle(function () use ($session) {
            $result = $this->engine->reject($session);

            return array_merge($result, ['state' => $this->engine->state($session)]);
        });
    }

    public function ledKransStop(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);
        $data = $request->validate(['index' => 'required|integer|min:0|max:19']);

        return $this->handle(function () use ($session, $data) {
            $result = $this->engine->ledKransStop($session, $data['index']);

            return array_merge($result, ['state' => $this->engine->state($session)]);
        });
    }

    public function themeSelect(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);
        $data = $request->validate(['theme_id' => 'required|integer|min:1|max:4']);

        return $this->handle(function () use ($session, $data) {
            $result = $this->engine->selectTheme($session, $data['theme_id']);

            return array_merge($result, ['state' => $this->engine->state($session)]);
        });
    }

    public function themeAnswer(Request $request, GameSession $session)
    {
        $this->authorizeSession($session);
        $data = $request->validate(['question_id' => 'required|integer', 'answer' => 'nullable']);

        return $this->handle(function () use ($session, $data) {
            $result = $this->engine->themeAnswer($session, $data['question_id'], $data['answer'] ?? null);

            return array_merge($result, ['state' => $this->engine->state($session)]);
        });
    }

    private function authorizeSession(GameSession $session): void
    {
        abort_unless($session->user_id === Auth::id(), 403);
    }

    private function handle(callable $callback)
    {
        try {
            return response()->json($callback());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
