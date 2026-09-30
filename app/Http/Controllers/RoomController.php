<?php

namespace App\Http\Controllers;

use App\Models\GameMember;
use App\Models\GameRoom;
use App\Services\GameEngine;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private GameEngine $engine) {}

    private function me(GameRoom $room): GameMember
    {
        $me = $room->match->members()->where('user_id', auth()->id())->first();
        abort_unless($me, 403, 'You are not in this room.');

        return $me;
    }

    private function run(GameRoom $room, \Closure $fn)
    {
        $me = $this->me($room);
        try {
            return response()->json($fn($me, $room->match) ?? ['ok' => true]);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(GameRoom $room)
    {
        $member = $room->match?->members()->where('user_id', auth()->id())->first();
        if (! $member || $room->status === 'closed') {
            return redirect()->route('lobby');
        }

        return view('room', ['room' => $room]);
    }

    public function state(GameRoom $room)
    {
        $this->me($room);

        return response()->json($this->engine->state($room, auth()->user()));
    }

    public function ready(GameRoom $room)
    {
        return $this->run($room, fn ($me) => $this->engine->toggleReady($me));
    }

    public function start(GameRoom $room)
    {
        return $this->run($room, fn ($me, $m) => $this->engine->start($m, $me));
    }

    public function endMatch(GameRoom $room)
    {
        return $this->run($room, fn ($me, $m) => $this->engine->endMatch($m, $me));
    }

    public function chat(Request $r, GameRoom $room)
    {
        $text = (string) $r->input('text');
        $res = $this->run($room, fn ($me, $m) => $this->engine->say($m, $me, $text));

        $user = auth()->user();
        $me = $room->match?->members()->where('user_id', $user->user_id)->with(['character', 'team'])->first();
        $cls = config('game.classes');
        $icon = $cls[$me?->character?->cha_class]['emoji'] ?? '💬';
        $team = $me?->team?->team_name ? strtolower($me->team->team_name) : null;

        broadcast(new \App\Events\ChatMessageSent(
            message: $text,
            sender: $user->username,
            type: 'chat',
            team: $team,
            icon: $icon,
            roomId: $room->room_id,
        ));

        return $res;
    }

    public function roll(GameRoom $room)
    {
        return $this->run($room, fn ($me, $m) => $this->engine->roll($m, $me));
    }

    public function end(GameRoom $room)
    {
        return $this->run($room, fn ($me, $m) => $this->engine->endTurn($m, $me));
    }

    public function leave(GameRoom $room)
    {
        $user = auth()->user();
        $res = $this->run($room, fn ($me, $m) => $this->engine->leave($m, $me));

        $msgText = "🚪 {$user->username} ออกจากห้องแล้ว";
        \App\Models\ChatMessage::create([
            'room_id' => $room->room_id,
            'match_id' => $room->match?->match_id,
            'member_id' => null,
            'message' => $msgText,
        ]);

        broadcast(new \App\Events\ChatMessageSent(
            message: $msgText,
            sender: 'ระบบ',
            type: 'system',
            team: 'system',
            icon: '🚪',
            roomId: $room->room_id,
        ));

        return $res;
    }

    public function move(Request $r, GameRoom $room)
    {
        return $this->run($room, fn ($me, $m) => $this->engine->move($m, $me, (int) $r->input('x'), (int) $r->input('y')));
    }

    public function attack(Request $r, GameRoom $room)
    {
        return $this->run($room, fn ($me, $m) => $this->engine->attack($m, $me, (int) $r->input('target')));
    }

    public function item(Request $r, GameRoom $room)
    {
        return $this->run($room, fn ($me, $m) => $this->engine->useItem($m, $me, (int) $r->input('id')));
    }
}
