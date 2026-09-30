<?php

use App\Models\GameMap;
use App\Models\GameMatch;
use App\Models\GameMember;
use App\Models\GameRoom;
use App\Services\GameEngine;

test('players can create and enter a game room', function () {
    $user = createDndPlayer();

    $response = $this->actingAs($user)->post(route('rooms.store'), [
        'room_name' => 'Test Room',
        'map' => 'forest',
        'turn_time' => '30',
    ]);

    $room = GameRoom::where('room_name', 'Test Room')->firstOrFail();

    $response->assertRedirect(route('rooms.show', $room->room_id));
    expect(GameMatch::where('room_id', $room->room_id)->exists())->toBeTrue();
    expect(GameMap::where('match_match_id', $room->match->match_id)->exists())->toBeTrue();
    expect(GameMember::where('user_id', $user->user_id)->where('match_id', $room->match->match_id)->exists())->toBeTrue();

    $this->get(route('rooms.show', $room->room_id))->assertOk();
    $this->get('/rooms/'.$room->room_id.'/state')->assertOk();
    $this->get('/rooms/'.$room->room_id.'/state')->assertJsonPath('me.host', true);
});

test('the host can start after four players are ready', function () {
    $engine = app(GameEngine::class);
    $host = createDndPlayer();
    $room = $engine->createRoom($host, 'Four Player Room', null, 'forest', 30);
    $match = $room->match;

    foreach (range(1, 3) as $index) {
        $player = createDndPlayer();
        $member = $engine->addMember($match, $player);
        $engine->toggleReady($member);
    }

    $hostMember = $match->members()->orderBy('member_id')->firstOrFail();
    $engine->start($match, $hostMember);

    expect($room->fresh()->status)->toBe('playing');
    expect($match->fresh()->members()->whereNotNull('team_id')->count())->toBe(4);
});
