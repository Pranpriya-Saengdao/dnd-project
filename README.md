# D&D Web Game (Laravel)

These files are an **overlay**: copy them on top of a fresh Laravel 11/12 project.

```bash
composer create-project laravel/laravel dnd-game
cd dnd-game
# copy everything from this folder into dnd-game/ (merge, overwrite routes/web.php, app/Models/User.php, DatabaseSeeder.php)

# remove Laravel's default users migration (it would clash with the "Users" table)
rm database/migrations/0001_01_01_000000_create_users_table.php
```

In `.env` use file drivers (turn state is kept in the cache, sessions in files):

```
DB_CONNECTION=sqlite        # or mysql
SESSION_DRIVER=file
CACHE_STORE=file
```

```bash
php artisan migrate --seed
php artisan serve
```

Admin account: `admin` / `admin1234` (choose the **Admin** tab on the login page).
To test multiplayer, log in with different browsers (or private windows) using different accounts.

## Rules implemented
- Character is **randomly assigned** at sign-up (class + varied stats), saved in `Character` and linked from `Users.Character_character_id`.
- Turn: **Roll D10** = movement points (1 tile per point, 8 directions) → **Attack** once (damage = ATK + D10 + buff; D10 = 1 misses, D10 = 10 doubles damage) → **End turn**. Turn timer auto-skips.
- Attack range depends on the class (Wizard 3, Ranger 4, others 1). No special skills.
- Chests (📦) give a random item. Items: Heal Potion (Consumable), Strength Potion (Buff, this turn only) — editable by admin.
- Teams A/B are assigned automatically at start; the last team standing wins → `Game_Result`.
