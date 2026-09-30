<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GameEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $r)
    {
        $cred = $r->validate(['username' => 'required', 'password' => 'required']);
        $as = $r->input('as', 'player');

        if (! Auth::attempt($cred)) {
            return back()->withInput($r->only('username', 'as'))->withErrors(['username' => 'Wrong username or password.']);
        }
        if ($as === 'admin' && ! Auth::user()->isAdmin()) {
            Auth::logout();

            return back()->withInput($r->only('username', 'as'))->withErrors(['username' => 'This account is not an admin.']);
        }
        $r->session()->regenerate();

        return redirect()->route(Auth::user()->isAdmin() && $as === 'admin' ? 'admin.dashboard' : 'lobby');
    }

    public function register(Request $r, GameEngine $engine)
    {
        $d = $r->validate([
            'email' => 'required|email|max:45|unique:Users,email',
            'username' => 'required|string|max:50|unique:Users,username',
            'password' => 'required|string|min:6|max:100',
        ]);

        $user = DB::transaction(function () use ($d, $engine) {
            $ch = $engine->createCharacter();   // random character
            $engine->starterItems($ch);

            return User::create([
                'username' => $d['username'], 'email' => $d['email'], 'password' => Hash::make($d['password']),
                'role' => 'player', 'character_character_id' => $ch->character_id,
            ]);
        });

        Auth::login($user);

        return redirect()->route('lobby');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('login');
    }
}
