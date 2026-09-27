<?php

namespace App\Http\Controllers;

use App\Models\CombatLog;
use App\Models\DiceRoll;
use App\Models\GameMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameController extends Controller
{
    public function show(GameMatch $gameMatch, Request $request)
    {
        $gameMatch->members()->where('user_id', $request->user()->id)->firstOrFail();

        return view('game.show', ['gameMatch' => $gameMatch]);
    }

    public function state(GameMatch $gameMatch, Request $request)
    {
        $gameMatch->members()->where('user_id', $request->user()->id)->firstOrFail();

        $members = $gameMatch->members()->with(['user', 'character'])->orderBy('turn_order')->get();
        $players = [];
        $currentPlayerId = null;
        $currentPlayerName = null;

        foreach ($members as $member) {
            if ($member->turn_order == $gameMatch->current_turn_order) {
                $currentPlayerId = $member->id;
                $currentPlayerName = $member->user->name;
            }

            $players[] = [
                'id' => $member->id,
                'name' => $member->user->name,
                'is_me' => $member->user_id == $request->user()->id,
                'class' => $member->character->name,
                'team' => $member->team,
                'hp' => $member->current_hp,
                'is_alive' => (bool) $member->is_alive,
                'position' => ['x' => $member->position_x, 'y' => $member->position_y],
                'skill_uses_remaining' => $member->skill_uses_remaining,
            ];
        }

        $logs = $gameMatch->combatLogs()
            ->with(['attacker.user', 'target.user', 'diceRoll'])
            ->orderBy('id', 'DESC')
            ->take(10)
            ->get();
        $recentCombat = [];

        foreach ($logs as $log) {
            $recentCombat[] = [
                'attacker' => $log->attacker->user->name,
                'target' => $log->target->user->name,
                'type' => $log->attack_type,
                'roll' => $log->diceRoll->result,
                'damage' => $log->damage,
                'round' => $log->round_number,
            ];
        }

        return response()->json([
            'match' => [
                'id' => $gameMatch->id,
                'map' => $gameMatch->map_key,
                'status' => $gameMatch->status,
                'round' => $gameMatch->current_round,
                'current_turn_order' => $gameMatch->current_turn_order,
                'current_player_id' => $currentPlayerId,
                'current_player_name' => $currentPlayerName,
                'winner_team' => $gameMatch->winner_team,
                'has_moved_this_turn' => (bool) $gameMatch->has_moved_this_turn,
                'has_positioned_this_turn' => (bool) $gameMatch->has_positioned_this_turn,
                'has_acted_this_turn' => (bool) $gameMatch->has_acted_this_turn,
            ],
            'players' => $players,
            'recent_combat' => $recentCombat,
        ]);
    }

    public function rollMovement(GameMatch $gameMatch, Request $request)
    {
        $member = $gameMatch->members()->where('user_id', $request->user()->id)->firstOrFail();

        if ($gameMatch->status != 'active') {
            throw ValidationException::withMessages(['match' => 'แมตช์นี้ยังไม่เริ่มหรือจบไปแล้ว']);
        }
        if ($member->turn_order != $gameMatch->current_turn_order) {
            throw ValidationException::withMessages(['turn' => 'ยังไม่ใช่เทิร์นของคุณ']);
        }
        if ($gameMatch->has_moved_this_turn) {
            throw ValidationException::withMessages(['movement' => 'คุณทอยเพื่อเดินในเทิร์นนี้ไปแล้ว']);
        }

        DB::beginTransaction();
        try {
            $roll = new DiceRoll();
            $roll->game_match_id = $gameMatch->id;
            $roll->game_member_id = $member->id;
            $roll->purpose = 'movement';
            $roll->result = random_int(1, 10);
            $roll->rolled_at = now();
            $roll->save();

            $gameMatch->has_moved_this_turn = true;
            $gameMatch->save();
            DB::commit();

            return response()->json([
                'purpose' => 'movement',
                'result' => $roll->result,
                'message' => 'คุณเดินได้ ' . $roll->result . ' ช่อง',
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function move(GameMatch $gameMatch, Request $request)
    {
        $validated = $request->validate([
            'position_x' => ['required', 'integer', 'between:0,9'],
            'position_y' => ['required', 'integer', 'between:0,9'],
        ]);
        $member = $gameMatch->members()->where('user_id', $request->user()->id)->firstOrFail();

        if ($gameMatch->status != 'active' || $member->turn_order != $gameMatch->current_turn_order) {
            throw ValidationException::withMessages(['turn' => 'ยังไม่ใช่เทิร์นของคุณ']);
        }
        if (! $gameMatch->has_moved_this_turn) {
            throw ValidationException::withMessages(['movement' => 'กรุณาทอยเต๋าเดินก่อน']);
        }
        if ($gameMatch->has_positioned_this_turn || $gameMatch->has_acted_this_turn) {
            throw ValidationException::withMessages(['movement' => 'คุณย้ายตำแหน่งในเทิร์นนี้ไปแล้ว']);
        }

        $roll = $gameMatch->diceRolls()->where('game_member_id', $member->id)
            ->where('purpose', 'movement')->orderBy('id', 'DESC')->firstOrFail();
        $distance = abs($member->position_x - $validated['position_x'])
            + abs($member->position_y - $validated['position_y']);

        if ($distance > $roll->result) {
            throw ValidationException::withMessages(['position' => 'ตำแหน่งใหม่ไกลกว่าจำนวนช่องที่ทอยได้']);
        }
        if ($gameMatch->members()->where('position_x', $validated['position_x'])
            ->where('position_y', $validated['position_y'])->where('is_alive', true)
            ->where('id', '!=', $member->id)->exists()) {
            throw ValidationException::withMessages(['position' => 'มีผู้เล่นยืนอยู่ในช่องนี้แล้ว']);
        }

        $member->position_x = $validated['position_x'];
        $member->position_y = $validated['position_y'];
        $member->save();
        $gameMatch->has_positioned_this_turn = true;
        $gameMatch->save();

        return response()->json(['message' => 'ย้ายตำแหน่งแล้ว', 'position' => [
            'x' => $member->position_x, 'y' => $member->position_y,
        ]]);
    }

    public function attack(GameMatch $gameMatch, Request $request)
    {
        $validated = $request->validate([
            'target_member_id' => ['required', 'integer'],
            'attack_type' => ['required', 'in:normal,skill'],
        ]);

        $attacker = $gameMatch->members()
            ->with('character')
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $target = $gameMatch->members()->where('id', $validated['target_member_id'])->firstOrFail();

        if ($gameMatch->status != 'active') {
            throw ValidationException::withMessages(['match' => 'แมตช์นี้ยังไม่เริ่มหรือจบไปแล้ว']);
        }
        if ($attacker->turn_order != $gameMatch->current_turn_order) {
            throw ValidationException::withMessages(['turn' => 'ยังไม่ใช่เทิร์นของคุณ']);
        }
        if ($gameMatch->has_acted_this_turn) {
            throw ValidationException::withMessages(['action' => 'คุณใช้แอ็กชันในเทิร์นนี้ไปแล้ว']);
        }
        if (! $gameMatch->has_moved_this_turn) {
            throw ValidationException::withMessages(['movement' => 'กรุณาทอยเต๋าเดินก่อนทำแอ็กชัน']);
        }
        if (! $attacker->is_alive || ! $target->is_alive) {
            throw ValidationException::withMessages(['target' => 'ผู้โจมตีและเป้าหมายต้องยังมีชีวิต']);
        }
        if ($attacker->team == $target->team) {
            throw ValidationException::withMessages(['target' => 'โจมตีได้เฉพาะผู้เล่นฝั่งตรงข้าม']);
        }
        if ($validated['attack_type'] == 'skill' && $attacker->skill_uses_remaining < 1) {
            throw ValidationException::withMessages(['skill' => 'ตัวละครนี้ใช้สกิลครบ 2 ครั้งแล้ว']);
        }

        $distance = abs($attacker->position_x - $target->position_x)
            + abs($attacker->position_y - $target->position_y);
        $rangeName = $validated['attack_type'] == 'skill' ? 'skill_range' : 'attack_range';
        if ($distance > $attacker->character->$rangeName) {
            throw ValidationException::withMessages(['target' => 'เป้าหมายอยู่นอกระยะโจมตี']);
        }

        DB::beginTransaction();
        try {
            $roll = new DiceRoll();
            $roll->game_match_id = $gameMatch->id;
            $roll->game_member_id = $attacker->id;
            $roll->purpose = $validated['attack_type'] == 'skill' ? 'skill' : 'attack';
            $roll->result = random_int(1, 10);
            $roll->rolled_at = now();
            $roll->save();

            if ($validated['attack_type'] == 'normal') {
                $stat = $attacker->character->attack;
            } else {
                $stat = $this->getSkillStat($attacker->character);
                $attacker->skill_uses_remaining--;
                $attacker->save();
            }

            $damage = $stat + $roll->result;
            $target->current_hp = max(0, $target->current_hp - $damage);
            $target->is_alive = $target->current_hp > 0;
            $target->save();

            $log = new CombatLog();
            $log->game_match_id = $gameMatch->id;
            $log->attacker_member_id = $attacker->id;
            $log->target_member_id = $target->id;
            $log->dice_roll_id = $roll->id;
            $log->attack_type = $validated['attack_type'];
            $log->damage = $damage;
            $log->round_number = $gameMatch->current_round;
            $log->save();

            $gameMatch->has_acted_this_turn = true;

            $opponentsAlive = $gameMatch->members()
                ->where('team', $target->team)
                ->where('is_alive', true)
                ->count();

            if ($opponentsAlive == 0) {
                $gameMatch->status = 'finished';
                $gameMatch->winner_team = $attacker->team;
                $gameMatch->ended_at = now();
            } else {
                $this->nextTurn($gameMatch);
            }

            $gameMatch->save();
            DB::commit();

            return response()->json([
                'attack_type' => $log->attack_type,
                'dice_roll' => $roll->result,
                'damage' => $log->damage,
                'target' => [
                    'id' => $target->id,
                    'current_hp' => $target->current_hp,
                    'is_alive' => (bool) $target->is_alive,
                ],
                'match' => [
                    'status' => $gameMatch->status,
                    'winner_team' => $gameMatch->winner_team,
                    'current_round' => $gameMatch->current_round,
                    'current_turn_order' => $gameMatch->current_turn_order,
                ],
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    private function getSkillStat($character)
    {
        if ($character->name == 'Wizard') {
            return $character->intelligence;
        }
        if ($character->name == 'Ranger' || $character->name == 'Rogue') {
            return $character->dexterity;
        }
        if ($character->name == 'Paladin' || $character->name == 'Sorcerer') {
            return $character->charisma;
        }

        throw ValidationException::withMessages(['character' => 'ไม่พบค่าสถานะสำหรับคลาสนี้']);
    }

    private function nextTurn(GameMatch $gameMatch)
    {
        $members = $gameMatch->members()->where('is_alive', true)->orderBy('turn_order')->get();
        $nextTurnOrder = null;

        foreach ($members as $member) {
            if ($member->turn_order > $gameMatch->current_turn_order) {
                $nextTurnOrder = $member->turn_order;
                break;
            }
        }

        if ($nextTurnOrder == null) {
            $nextTurnOrder = $members[0]->turn_order;
            $gameMatch->current_round++;
        }

        $gameMatch->current_turn_order = $nextTurnOrder;
        $gameMatch->has_moved_this_turn = false;
        $gameMatch->has_positioned_this_turn = false;
        $gameMatch->has_acted_this_turn = false;
    }
}
