<?php

namespace App\Http\Controllers;

use App\Models\CombatLog;
use App\Models\GameMatch;
use App\Models\GameResult;
use App\Models\GameRoom;
use App\Models\Item;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $top = DB::table('Game_Result as r')
            ->join('Game_Member as m', 'm.member_id', '=', 'r.member_id')
            ->join('Users as u', 'u.user_id', '=', 'm.user_id')
            ->where('r.result', 1)
            ->select('u.username', DB::raw('count(*) as wins'), DB::raw('sum(r.total_damage) as damage'))
            ->groupBy('u.user_id', 'u.username')->orderByDesc('wins')->limit(5)->get();

        return view('admin.dashboard', [
            'totalUsers' => User::where('role', 'player')->count(),
            'totalGames' => GameMatch::whereNotNull('ended_at')->count(),
            'playing' => GameRoom::where('status', 'playing')->count(),
            'top' => $top,
        ]);
    }

    /* ---- users ---- */
    public function users()
    {
        return view('admin.users', ['users' => User::withTrashed()->with('character')->orderBy('user_id')->get()]);
    }

    public function updateUser(Request $r, $id)
    {
        $u = User::withTrashed()->findOrFail($id);
        $d = $r->validate([
            'username' => 'required|max:50',
            'email' => 'required|email|max:45|unique:Users,email,'.$u->user_id.',user_id',
            'role' => 'required|in:player,admin',
        ]);
        $u->update($d);

        return back()->with('ok', 'User updated.');
    }

    public function deleteUser($id)
    {
        abort_if($id == auth()->id(), 422, 'You cannot delete yourself.');
        User::findOrFail($id)->delete();   // soft delete (deleteAt)

        return back()->with('ok', 'User deleted.');
    }

    public function restoreUser($id)
    {
        User::withTrashed()->findOrFail($id)->restore();

        return back()->with('ok', 'User restored.');
    }

    /* ---- items ---- */
    public function items()
    {
        return view('admin.items', ['items' => Item::orderBy('item_id')->get()]);
    }

    private function itemRules(): array
    {
        return ['item_name' => 'required|max:50', 'item_type' => 'required|in:Consumable,Buff',
            'description' => 'nullable|max:255', 'effect_value' => 'required|integer|min:0|max:999'];
    }

    public function storeItem(Request $r)
    {
        Item::create($r->validate($this->itemRules()));

        return back()->with('ok', 'Item added.');
    }

    public function updateItem(Request $r, Item $item)
    {
        $item->update($r->validate($this->itemRules()));

        return back()->with('ok', 'Item updated.');
    }

    public function deleteItem(Item $item)
    {
        $item->delete();

        return back()->with('ok', 'Item deleted.');
    }

    /* ---- game records ---- */
    public function games()
    {
        $matches = GameMatch::with(['room', 'members.user'])->orderByDesc('match_id')->limit(100)->get();

        return view('admin.games', ['matches' => $matches]);
    }

    public function game(GameMatch $match)
    {
        $match->load(['room', 'members.user', 'members.character']);
        $teams = Team::where('match_id', $match->match_id)->pluck('team_name', 'team_id');
        $results = GameResult::where('match_id', $match->match_id)->get()->keyBy('member_id');
        $log = CombatLog::with(['attacker.user', 'target.user', 'diceRoll'])->where('match_id', $match->match_id)->orderBy('combat_id')->get();

        return view('admin.game', compact('match', 'teams', 'results', 'log'));
    }
}
