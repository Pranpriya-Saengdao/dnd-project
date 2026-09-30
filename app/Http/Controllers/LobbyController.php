<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Services\GameEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LobbyController extends Controller
{
    public function reroll(Request $r, GameEngine $engine)
    {
        if ($m = $engine->activeMembership($r->user())) {
            return redirect()->route('rooms.show', $m->gameMatch->room_id)
                ->withErrors(['character' => 'Leave your current room before rerolling your character.']);
        }

        DB::transaction(function () use ($r, $engine) {
            $user = $r->user();
            $character = $engine->createCharacter();
            $engine->starterItems($character);
            $user->character()->associate($character);
            $user->save();
        });

        return redirect()->route('lobby')->with('status', 'A new random character is ready!');
    }

    public function index(Request $r, GameEngine $engine)
    {
        $user = $r->user()->load('character.items.item');
        $character = $user->character;

        if (! $character) {
            $character = $engine->createCharacter();
            $engine->starterItems($character);
            $user->character()->associate($character);
            $user->save();
        }

        $q = GameRoom::where('status', 'waiting')->with(['match.map', 'match.members'])->latest('room_id');
        if ($s = $r->query('q')) {
            $q->where('room_name', 'like', "%$s%");
        }

        $rooms = $q->get()->filter(fn ($room) => $room->match && $room->match->map
            && (! $r->query('map') || $room->match->map->map_name === $r->query('map')));

        return view('lobby', [
            'user' => $user, 'ch' => $character, 'class' => config('game.classes')[$character->cha_class] ?? null,
            'rooms' => $rooms, 'mine' => $engine->activeMembership($user),
        ]);
    }

    public function create(Request $r, GameEngine $engine)
    {
        if ($m = $engine->activeMembership($r->user())) {
            return redirect()->route('rooms.show', $m->gameMatch->room_id);
        }

        return view('rooms.create');
    }

    public function store(Request $r, GameEngine $engine)
    {
        if ($m = $engine->activeMembership($r->user())) {
            return redirect()->route('rooms.show', $m->gameMatch->room_id);
        }
        $d = $r->validate([
            'room_name' => 'required|string|max:45',
            'room_password' => 'nullable|string|max:50',
            'map' => ['required', Rule::in(array_keys(config('game.maps')))],
            'turn_time' => 'required|in:15,30,60',
        ]);
        $room = $engine->createRoom($r->user(), $d['room_name'], $d['room_password'] ?? null, $d['map'], (int) $d['turn_time']);

        return redirect()->route('rooms.show', $room->room_id);
    }

    public function join(Request $r, GameRoom $room, GameEngine $engine)
    {
        if ($m = $engine->activeMembership($r->user())) {
            return redirect()->route('rooms.show', $m->gameMatch->room_id);
        }
        $match = $room->match;
        if ($room->status !== 'waiting') {
            return back()->withErrors(['join' => 'That room already started.']);
        }
        if ($room->room_password && $room->room_password !== (string) $r->input('password')) {
            return back()->withErrors(['join' => 'Wrong room password.']);
        }
        if ($match->members()->count() >= config('game.max_players')) {
            return back()->withErrors(['join' => 'Room is full.']);
        }

        $engine->addMember($match, $r->user());

        $user = $r->user();
        $cls = config('game.classes');
        $icon = $cls[$user->character?->cha_class]['emoji'] ?? '🧙';

        $msgText = "{$icon} {$user->username} เข้าร่วมห้องแล้ว";

        \App\Models\ChatMessage::create([
            'room_id' => $room->room_id,
            'match_id' => $match->match_id,
            'member_id' => null,
            'message' => $msgText,
        ]);

        broadcast(new \App\Events\ChatMessageSent(
            message: $msgText,
            sender: 'ระบบ',
            type: 'system',
            team: 'system',
            icon: '🎮',
            roomId: $room->room_id,
        ));

        return redirect()->route('rooms.show', $room->room_id);
    }
}
