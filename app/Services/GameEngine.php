<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\ChatMessage;
use App\Models\CombatLog;
use App\Models\DiceRoll;
use App\Models\GameMap;
use App\Models\GameMatch;
use App\Models\GameMember;
use App\Models\GameResult;
use App\Models\GameRoom;
use App\Models\Item;
use App\Models\Skill;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GameEngine
{
    const ACTIVE = ['waiting', 'playing'];

    /* ------------------------------------------------------------ characters */

    /** Randomly generate a character (class + slightly varied stats). */
    public function createCharacter(): Character
    {
        $classes = config('game.classes');
        $class = array_rand($classes);
        $c = $classes[$class];
        $names = config('game.names');

        return Character::create([
            'character_name' => $names[array_rand($names)],
            'cha_class' => $class,
            'max_hp' => $c['hp'] + random_int(-5, 5),
            'attack' => $c['attack'] + random_int(0, 2),
            'charisma' => $c['wisdom'],
            'dexterity' => $c['dexterity'],
            'intelligence' => $c['intelligence'],
        ]);
    }

    public function starterItems(Character $ch): void
    {
        $defaults = [
            'Heal Potion' => ['item_type' => 'Consumable', 'description' => 'Restore HP', 'effect_value' => 15],
            'Strength Potion' => ['item_type' => 'Buff', 'description' => 'ATK +10 for this turn', 'effect_value' => 10],
        ];

        foreach ($defaults as $name => $attributes) {
            $item = Item::withTrashed()->firstOrCreate(['item_name' => $name], $attributes);
            if ($item->trashed()) {
                $item->restore();
            }

            $inventory = CharacterItem::withTrashed()->firstOrCreate(
                ['character_id' => $ch->character_id, 'item_id' => $item->item_id],
                ['quantity' => 1],
            );
            if ($inventory->trashed()) {
                $inventory->restore();
                $inventory->update(['quantity' => max(1, (int) $inventory->quantity)]);
            }
        }
    }

    /* ------------------------------------------------------------ rooms */

    public function activeMembership(User $u): ?GameMember
    {
        return GameMember::where('user_id', $u->user_id)
            ->whereHas('gameMatch.room', fn ($q) => $q->whereIn('status', self::ACTIVE))
            ->latest('member_id')->first();
    }

    public function createRoom(User $u, string $name, ?string $pw, string $mapKey, int $turnTime): GameRoom
    {
        return DB::transaction(function () use ($u, $name, $pw, $mapKey, $turnTime) {
            $room = GameRoom::create(['room_name' => $name, 'room_password' => $pw ?: null, 'status' => 'waiting']);
            $match = GameMatch::create(['room_id' => $room->room_id, 'current_turn' => 0, 'total_round' => 0]);
            GameMap::create([
                'match_match_id' => $match->match_id, 'map_name' => $mapKey,
                'width' => config('game.size'), 'height' => config('game.size'),
                'map_data' => json_encode(['turn_time' => $turnTime, 'grid' => config("game.maps.$mapKey.grid")]),
            ]);
            $this->addMember($match, $u, true);

            return $room;
        });
    }

    public function addMember(GameMatch $m, User $u, bool $host = false): GameMember
    {
        $ch = $u->character;

        return GameMember::create([
            'user_id' => $u->user_id, 'character_id' => $ch->character_id, 'match_id' => $m->match_id,
            'hp' => $ch->max_hp, 'is_ready' => $host ? 'Y' : 'N', 'is_alive' => 'Y',
        ]);
    }

    public function say(GameMatch $m, GameMember $me, string $text): void
    {
        $text = mb_substr(trim($text), 0, 255);
        if ($text === '') {
            return;
        }
        ChatMessage::create(['room_id' => $m->room_id, 'member_id' => $me->member_id, 'match_id' => $m->match_id, 'message' => $text]);
    }

    public function toggleReady(GameMember $me): void
    {
        $me->update(['is_ready' => $me->is_ready === 'Y' ? 'N' : 'Y']);
    }

    public function start(GameMatch $m, GameMember $me): void
    {
        DB::transaction(function () use ($m, $me) {
            $m->refresh()->load('room');
            $members = $m->members()->orderBy('member_id')->with('character')->get();
            if ($m->room->status !== 'waiting') {
                throw new \DomainException('Game already started.');
            }
            if ($members->first()->member_id != $me->member_id) {
                throw new \DomainException('Only the host can start.');
            }
            if ($members->count() < 2) {
                throw new \DomainException('Need at least 2 players.');
            }
            if ($members->skip(1)->contains(fn ($x) => $x->is_ready !== 'Y')) {
                throw new \DomainException('Everyone must be ready.');
            }

            $teams = [
                'A' => Team::create(['team_name' => 'Team A', 'match_id' => $m->match_id]),
                'B' => Team::create(['team_name' => 'Team B', 'match_id' => $m->match_id]),
            ];
            $spawns = config('game.spawns');
            $used = ['A' => 0, 'B' => 0];
            foreach ($members as $i => $mem) {
                $k = $i % 2 === 0 ? 'A' : 'B';
                [$x, $y] = $spawns[$k][$used[$k]++];
                $mem->update(['team_id' => $teams[$k]->team_id, 'position_x' => $x, 'position_y' => $y,
                    'hp' => $mem->character->max_hp, 'is_alive' => 'Y', 'is_ready' => 'Y']);
            }
            $m->update(['started_at' => now(), 'current_turn' => 0, 'total_round' => 1]);
            $m->room->update(['status' => 'playing']);
            $this->resetTurn($m);
        });
    }

    public function leave(GameMatch $m, GameMember $me): void
    {
        DB::transaction(function () use ($m, $me) {
            $m->refresh()->load('room');
            $status = $m->room->status;
            if ($status === 'waiting') {
                ChatMessage::where('member_id', $me->member_id)->delete();
                $me->delete();
                if (! $m->members()->exists()) {
                    $m->room->update(['status' => 'closed']);
                }
            } elseif ($status === 'playing') {
                $wasActive = $this->activeOf($m, $m->members()->orderBy('member_id')->get())->member_id == $me->member_id;
                $me->update(['hp' => 0, 'is_alive' => 'N']);
                $this->checkWinner($m);
                if (! $m->refresh()->ended_at && $wasActive) {
                    $this->advance($m);
                }
            }
        });
    }

    public function endMatch(GameMatch $m, GameMember $me): void
    {
        $m->refresh()->load('room');
        $host = $m->members()->orderBy('member_id')->first();
        if (! $host || $host->member_id != $me->member_id) {
            throw new \DomainException('Only the host can end this match.');
        }
        if ($m->room->status !== 'playing') {
            throw new \DomainException('This match is not running.');
        }

        DB::transaction(function () use ($m) {
            $m->update(['ended_at' => now()]);
            $m->room->update(['status' => 'closed']);
            Cache::forget($this->key($m));
        });
    }

    /* ------------------------------------------------------------ turn state */

    private function key(GameMatch $m): string
    {
        return 'turn:'.$m->match_id;
    }

    public function turnState(GameMatch $m): array
    {
        return Cache::get($this->key($m)) ?? ['rolled' => false, 'moves' => 0, 'attacked' => false, 'bonus' => 0, 'started' => time()];
    }

    private function saveTurn(GameMatch $m, array $ts): void
    {
        Cache::put($this->key($m), $ts, 86400);
    }

    private function resetTurn(GameMatch $m): void
    {
        $this->saveTurn($m, ['rolled' => false, 'moves' => 0, 'attacked' => false, 'bonus' => 0, 'started' => time()]);
    }

    private function mapData(GameMatch $m): array
    {
        return json_decode($m->map()->first()->map_data, true);
    }

    public function activeOf(GameMatch $m, $members): GameMember
    {
        return $members->values()[$m->current_turn % $members->count()];
    }

    public function advance(GameMatch $m): void
    {
        $members = $m->members()->orderBy('member_id')->get();
        $n = $members->count();
        $t = $m->current_turn;
        for ($i = 0; $i < $n; $i++) {
            $t++;
            if ($members[$t % $n]->is_alive === 'Y') {
                break;
            }
        }
        $m->update(['current_turn' => $t, 'total_round' => intdiv($t, $n) + 1]);
        $this->resetTurn($m);
    }

    /** Auto-skip the turn when the time limit is over. */
    public function tick(GameMatch $m): GameMatch
    {
        $m->load('room');
        if ($m->ended_at || $m->room->status !== 'playing') {
            return $m;
        }
        $limit = $this->mapData($m)['turn_time'] ?? 30;
        if (time() - $this->turnState($m)['started'] > $limit) {
            DB::transaction(function () use ($m, $limit) {
                if (time() - $this->turnState($m)['started'] > $limit) {
                    $this->advance($m->refresh());
                }
            });
        }

        return $m->refresh();
    }

    private function guardTurn(GameMatch $m, GameMember $me): array
    {
        $m->refresh()->load('room');
        $me->refresh();
        if ($m->ended_at || $m->room->status !== 'playing') {
            throw new \DomainException('The game is not running.');
        }
        if ($me->is_alive !== 'Y') {
            throw new \DomainException('You are down.');
        }
        $active = $this->activeOf($m, $m->members()->orderBy('member_id')->get());
        if ($active->member_id != $me->member_id) {
            throw new \DomainException('It is not your turn.');
        }

        return $this->turnState($m);
    }

    private function logRoll(GameMatch $m, GameMember $me, int $d, string $purpose): DiceRoll
    {
        return DiceRoll::create(['room_id' => $m->room_id, 'member_id' => $me->member_id, 'dice_result' => $d,
            'roll_purpose' => $purpose, 'match_match_id' => $m->match_id]);
    }

    /* ------------------------------------------------------------ actions */

    public function roll(GameMatch $m, GameMember $me): array
    {
        return DB::transaction(function () use ($m, $me) {
            $ts = $this->guardTurn($m, $me);
            if ($ts['rolled']) {
                throw new \DomainException('You already rolled for movement this turn.');
            }
            $d = random_int(1, config('game.dice_sides', 10));
            $this->logRoll($m, $me, $d, 'move');
            $ts['rolled'] = true;
            $ts['moves'] = $d;
            $this->saveTurn($m, $ts);

            return ['type' => 'roll', 'dice' => $d];
        });
    }

    public function move(GameMatch $m, GameMember $me, int $x, int $y): array
    {
        return DB::transaction(function () use ($m, $me, $x, $y) {
            $ts = $this->guardTurn($m, $me);
            if (! $ts['rolled']) {
                throw new \DomainException('Roll the D10 first.');
            }
            if ($ts['moves'] < 1) {
                throw new \DomainException('No movement left.');
            }
            $size = config('game.size');
            if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
                throw new \DomainException('Out of the map.');
            }
            if (max(abs($x - $me->position_x), abs($y - $me->position_y)) !== 1) {
                throw new \DomainException('Move one tile at a time.');
            }

            $data = $this->mapData($m);
            if ($data['grid'][$y][$x] === '#') {
                throw new \DomainException('That tile is blocked.');
            }
            if ($m->members()->where('is_alive', 'Y')->where('position_x', $x)->where('position_y', $y)->exists()) {
                throw new \DomainException('Someone is standing there.');
            }

            $me->update(['position_x' => $x, 'position_y' => $y]);
            $ts['moves']--;
            $this->saveTurn($m, $ts);

            $found = null;
            if ($data['grid'][$y][$x] === 'c') {
                $row = $data['grid'][$y];
                $row[$x] = '.';
                $data['grid'][$y] = $row;
                $m->map()->first()->update(['map_data' => json_encode($data)]);
                $found = $this->giveRandomItem($me);
            }

            return ['type' => 'move', 'found' => $found];
        });
    }

    private function giveRandomItem(GameMember $me): ?string
    {
        $item = Item::inRandomOrder()->first();
        if (! $item) {
            return null;
        }
        $ci = CharacterItem::where('character_id', $me->character_id)->where('item_id', $item->item_id)->first();
        $ci ? $ci->increment('quantity')
            : CharacterItem::create(['character_id' => $me->character_id, 'item_id' => $item->item_id, 'quantity' => 1]);

        return $item->item_name;
    }

    public function attack(GameMatch $m, GameMember $me, int $targetId): array
    {
        return DB::transaction(function () use ($m, $me, $targetId) {
            $ts = $this->guardTurn($m, $me);
            if ($ts['attacked']) {
                throw new \DomainException('You already attacked this turn.');
            }

            $t = $m->members()->with('user')->where('member_id', $targetId)->first();
            if (! $t || $t->is_alive !== 'Y') {
                throw new \DomainException('Invalid target.');
            }
            if ($t->team_id == $me->team_id) {
                throw new \DomainException('You cannot attack a teammate.');
            }

            $ch = $me->character;
            $range = config("game.classes.{$ch->cha_class}.range", 1);
            if (max(abs($t->position_x - $me->position_x), abs($t->position_y - $me->position_y)) > $range) {
                throw new \DomainException('Target is out of range.');
            }

            $d = random_int(1, config('game.dice_sides', 10));
            $roll = $this->logRoll($m, $me, $d, 'attack');
            $bonus = $ts['bonus'];
            $miss = $d === 1;
            $crit = $d === config('game.dice_sides', 10);
            $dmg = $miss ? 0 : ($ch->attack + $d + $bonus) * ($crit ? 2 : 1);

            $t->hp = max(0, $t->hp - $dmg);
            if ($t->hp === 0) {
                $t->is_alive = 'N';
            }
            $t->save();

            $skill = Skill::firstOrCreate(['skill_name' => 'Attack'], ['description' => 'Basic attack: ATK + D10']);
            CombatLog::create([
                'damage_dealt' => $dmg, 'turn_number' => $m->current_turn,
                'attacker_member_id' => $me->member_id, 'target_member_id' => $t->member_id,
                'room_id' => $m->room_id, 'skill_id' => $skill->skill_id,
                'dice_roll_id' => $roll->dice_roll_id, 'match_id' => $m->match_id,
            ]);

            $ts['attacked'] = true;
            $this->saveTurn($m, $ts);
            $this->checkWinner($m);

            return ['type' => 'attack', 'dice' => $d, 'atk' => $ch->attack, 'bonus' => $bonus, 'damage' => $dmg,
                'crit' => $crit, 'miss' => $miss, 'target' => $t->user->username, 'killed' => $t->hp === 0];
        });
    }

    public function useItem(GameMatch $m, GameMember $me, int $characterItemId): array
    {
        return DB::transaction(function () use ($m, $me, $characterItemId) {
            $ts = $this->guardTurn($m, $me);
            $ci = CharacterItem::with('item')->where('character_item_id', $characterItemId)
                ->where('character_id', $me->character_id)->where('quantity', '>', 0)->first();
            if (! $ci || ! $ci->item) {
                throw new \DomainException('Item not found.');
            }
            $item = $ci->item;

            if ($item->item_type === 'Buff') {
                $ts['bonus'] += (int) $item->effect_value;
                $this->saveTurn($m, $ts);
            } else {
                $me->update(['hp' => min($me->character->max_hp, $me->hp + (int) $item->effect_value)]);
            }
            $ci->decrement('quantity');

            return ['type' => 'item', 'name' => $item->item_name];
        });
    }

    public function endTurn(GameMatch $m, GameMember $me): void
    {
        DB::transaction(function () use ($m, $me) {
            $this->guardTurn($m, $me);
            $this->advance($m);
        });
    }

    private function checkWinner(GameMatch $m): void
    {
        $alive = $m->members()->where('is_alive', 'Y')->get();
        if ($alive->pluck('team_id')->unique()->count() > 1) {
            return;
        }

        $win = optional($alive->first())->team_id;
        $m->update(['ended_at' => now()]);
        $m->room()->first()->update(['status' => 'finished']);
        foreach ($m->members()->get() as $mem) {
            GameResult::create([
                'room_id' => $m->room_id, 'member_id' => $mem->member_id, 'match_id' => $m->match_id,
                'result' => $mem->team_id == $win ? 1 : 0,
                'total_damage' => (int) CombatLog::where('match_id', $m->match_id)->where('attacker_member_id', $mem->member_id)->sum('damage_dealt'),
            ]);
        }
    }

    /* ------------------------------------------------------------ state for the client */

    public function state(GameRoom $room, User $user): array
    {
        $m = $this->tick($room->match()->first());
        $room = $m->room()->first();
        $members = $m->members()->orderBy('member_id')->with(['user', 'character'])->get();
        $teams = Team::where('match_id', $m->match_id)->pluck('team_name', 'team_id');
        $data = $this->mapData($m);
        $cls = config('game.classes');
        $playing = $room->status === 'playing';
        $active = $playing ? $this->activeOf($m, $members) : null;
        // Keep host selection identical to start(): the first member in the
        // same ordered collection is the room creator. Avoid aggregate min()
        // here because Oracle and SQLite can return different scalar types.
        $hostId = $members->first()?->member_id;
        $meRow = $members->firstWhere('user_id', $user->user_id);
        $ts = $this->turnState($m);
        $limit = $data['turn_time'] ?? 30;

        $list = $members->map(fn ($x) => [
            'id' => $x->member_id, 'name' => $x->user->username, 'class' => $x->character->cha_class,
            'emoji' => $cls[$x->character->cha_class]['emoji'] ?? '❔', 'team' => $teams[$x->team_id] ?? null,
            'hp' => (int) $x->hp, 'max_hp' => (int) $x->character->max_hp,
            // Oracle NUMBER columns may be hydrated as numeric strings; send
            // actual JSON numbers so browser coordinate arithmetic is numeric.
            'x' => (int) $x->position_x, 'y' => (int) $x->position_y,
            'alive' => $x->is_alive === 'Y', 'ready' => $x->is_ready === 'Y',
            'host' => $x->member_id == $hostId, 'me' => $meRow && $x->member_id == $meRow->member_id,
        ])->values();

        $me = null;
        if ($meRow) {
            $myTurn = $active && $active->member_id == $meRow->member_id;
            $me = [
                'id' => $meRow->member_id, 'team' => $teams[$meRow->team_id] ?? null, 'host' => $meRow->member_id == $hostId,
                'attack' => $meRow->character->attack, 'range' => $cls[$meRow->character->cha_class]['range'] ?? 1,
                'myTurn' => (bool) $myTurn, 'rolled' => $myTurn && $ts['rolled'], 'moves' => $myTurn ? $ts['moves'] : 0,
                'attacked' => $myTurn && $ts['attacked'], 'bonus' => $myTurn ? $ts['bonus'] : 0,
                'inventory' => CharacterItem::with('item')->where('character_id', $meRow->character_id)->where('quantity', '>', 0)->get()
                    ->filter(fn ($c) => $c->item)
                    ->map(fn ($c) => ['id' => $c->character_item_id, 'name' => $c->item->item_name, 'type' => $c->item->item_type,
                        'desc' => $c->item->description, 'qty' => $c->quantity])->values(),
            ];
        }

        $chat = ChatMessage::where('match_id', $m->match_id)->with(['member.user', 'member.character', 'member.team'])->orderByDesc('message_id')->limit(40)->get()
            ->reverse()->map(function ($c) {
                $cls = config('game.classes');
                $isSystem = ! $c->member;

                return [
                    'name' => $c->member?->user?->username ?? 'ระบบ',
                    'text' => $c->message,
                    'time' => $c->createdat ? $c->createdat->format('H:i') : date('H:i'),
                    'type' => $isSystem ? 'system' : 'chat',
                    'team' => $isSystem ? 'system' : strtolower($c->member?->team?->team_name ?? 'default'),
                    'icon' => $isSystem ? '🎮' : ($cls[$c->member?->character?->cha_class]['emoji'] ?? '💬'),
                ];
            })->values();

        $log = CombatLog::where('match_id', $m->match_id)->with(['attacker.user', 'target.user', 'diceRoll'])
            ->orderByDesc('combat_id')->limit(6)->get()->map(function ($l) {
                $d = optional($l->diceRoll)->dice_result;
                $tag = $d === config('game.dice_sides', 10) ? ' CRIT!' : ($d === 1 ? ' MISS' : '');

                return "{$l->attacker->user->username} → {$l->target->user->username}: D10 {$d}, {$l->damage_dealt} dmg{$tag}";
            })->values();

        $result = null;
        if ($room->status === 'finished') {
            $rows = GameResult::with('member.user', 'member.character')->where('match_id', $m->match_id)->get();
            $result = [
                'winner' => optional($rows->firstWhere('result', 1))->member ? ($teams[$rows->firstWhere('result', 1)->member->team_id] ?? null) : null,
                'players' => $rows->map(fn ($r) => [
                    'name' => $r->member->user->username, 'emoji' => $cls[$r->member->character->cha_class]['emoji'] ?? '❔',
                    'team' => $teams[$r->member->team_id] ?? null, 'win' => $r->result == 1,
                    'hp' => $r->member->hp, 'max_hp' => $r->member->character->max_hp, 'damage' => $r->total_damage,
                ])->values(),
            ];
        }

        return [
            'room' => ['id' => $room->room_id, 'name' => $room->room_name, 'status' => $room->status, 'password' => $room->room_password],
            'map' => ['name' => config("game.maps.{$m->map()->first()->map_name}.label"), 'grid' => $data['grid'], 'turn_time' => $limit],
            'round' => $m->total_round, 'members' => $list, 'active' => $active ? $active->member_id : null,
            'time_left' => $playing ? max(0, $limit - (time() - $ts['started'])) : null,
            'me' => $me, 'chat' => $chat, 'log' => $log, 'result' => $result,
            'max_players' => config('game.max_players'),
        ];
    }
}
