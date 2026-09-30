<?php

use App\Models\CharacterItem;
use App\Models\Item;
use App\Models\User;

test('new players register with a character and starter items', function () {
    $this->post(route('register.post'), [
        'username' => 'new-player',
        'email' => 'new-player@example.test',
        'password' => 'password',
    ])->assertRedirect(route('lobby'));

    $user = User::where('username', 'new-player')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->character)->not->toBeNull();
    expect(Item::whereIn('item_name', ['Heal Potion', 'Strength Potion'])->count())->toBe(2);
    expect(CharacterItem::where('character_id', $user->character_character_id)->count())->toBe(2);
});

test('registration validates required player details', function () {
    $this->from(route('register'))->post(route('register.post'), [
        'username' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertRedirect(route('register'))->assertSessionHasErrors(['username', 'email', 'password']);

    $this->assertDatabaseCount('Users', 0);
});
