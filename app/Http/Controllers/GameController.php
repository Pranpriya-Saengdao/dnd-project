<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Events\PlayerMoved;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index()
    {
        return view('game');
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'player_id' => ['required', 'integer'],
            'x' => ['required', 'integer', 'min:0', 'max:9'],
            'y' => ['required', 'integer', 'min:0', 'max:9'],
        ]);

        broadcast(new PlayerMoved(
            $data['player_id'],
            $data['x'],
            $data['y'],
        ));

        return response()->json([
            'success' => true,
        ]);
    }

    public function chat(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:255'],
            'player_id' => ['nullable', 'integer'],
            'sender' => ['nullable', 'string', 'max:50'],
            'team' => ['nullable', 'string', 'in:red,blue,system'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        $playerId = (int) ($data['player_id'] ?? 1);
        $team = $data['team'] ?? ($playerId === 1 ? 'red' : 'blue');
        $icon = $data['icon'] ?? ($playerId === 1 ? '🧙' : '🧝');
        $sender = $data['sender'] ?? (auth()->check() ? auth()->user()->username : "Player {$playerId}");

        broadcast(new ChatMessageSent(
            message: $data['message'],
            sender: $sender,
            type: 'chat',
            team: $team,
            icon: $icon,
        ));

        return response()->json([
            'success' => true,
        ]);
    }

    public function join(Request $request)
    {
        $data = $request->validate([
            'player_id' => ['nullable', 'integer'],
            'team' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
        ]);

        $playerId = (int) ($data['player_id'] ?? 1);
        $team = $data['team'] ?? ($playerId === 1 ? 'red' : 'blue');
        $teamName = $team === 'red' ? 'ทีมแดง' : 'ทีมน้ำเงิน';
        $icon = $data['icon'] ?? ($playerId === 1 ? '🧙' : '🧝');
        $sender = auth()->check() ? auth()->user()->username : "Player {$playerId}";

        broadcast(new ChatMessageSent(
            message: "{$icon} {$sender} ({$teamName}) เข้าร่วมเกมแล้ว",
            sender: 'ระบบ',
            type: 'system',
            team: 'system',
            icon: '🎮',
        ));

        return response()->json([
            'success' => true,
        ]);
    }
}
