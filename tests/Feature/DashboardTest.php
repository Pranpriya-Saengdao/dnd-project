<?php

test('guests are redirected to login before entering the lobby', function () {
    $this->get(route('lobby'))->assertRedirect(route('login'));
});

test('authenticated players can view the lobby', function () {
    $this->actingAs(createDndPlayer())
        ->get(route('lobby'))
        ->assertOk()
        ->assertSee('Your character');
});

test('only admins can access the admin dashboard', function () {
    $this->actingAs(createDndPlayer())
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs(createDndPlayer('admin'))
        ->get(route('admin.dashboard'))
        ->assertOk();
});
