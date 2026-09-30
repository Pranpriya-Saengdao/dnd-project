<?php

use Illuminate\Support\Facades\Auth;

test('login page can be rendered', function () {
    $this->get(route('login'))->assertOk();
});

test('players can log in with their username', function () {
    $user = createDndPlayer();

    $this->post(route('login.post'), [
        'username' => $user->username,
        'password' => 'password',
    ])->assertRedirect(route('lobby'));

    $this->assertAuthenticatedAs($user);
});

test('invalid passwords are rejected', function () {
    $user = createDndPlayer();

    $this->from(route('login'))->post(route('login.post'), [
        'username' => $user->username,
        'password' => 'incorrect',
    ])->assertRedirect(route('login'))->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('players can log out', function () {
    $this->actingAs(createDndPlayer())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse();
});
