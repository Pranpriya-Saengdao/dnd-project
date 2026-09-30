<?php

return [
    'max_players' => 4,
    'size' => 10,
    'dice_sides' => 10,

    // Characters are assigned randomly at registration (no manual class selection).
    'classes' => [
        'Wizard' => ['emoji' => '🧙', 'hp' => 50, 'attack' => 2, 'intelligence' => 5, 'dexterity' => 1, 'wisdom' => 4, 'range' => 3, 'bio' => 'An apprentice of the Great Library, seeking to prove his mastery of arcane arts.'],
        'Fighter' => ['emoji' => '🛡️', 'hp' => 70, 'attack' => 5, 'intelligence' => 1, 'dexterity' => 2, 'wisdom' => 2, 'range' => 1, 'bio' => 'A disciplined knight who never leaves the front line.'],
        'Barbarian' => ['emoji' => '🪓', 'hp' => 80, 'attack' => 6, 'intelligence' => 1, 'dexterity' => 1, 'wisdom' => 1, 'range' => 1, 'bio' => 'A raging warrior with more muscle than manners.'],
        'Ranger' => ['emoji' => '🏹', 'hp' => 55, 'attack' => 4, 'intelligence' => 2, 'dexterity' => 5, 'wisdom' => 3, 'range' => 4, 'bio' => 'A patient hunter who strikes from far away.'],
        'Rogue' => ['emoji' => '🗡️', 'hp' => 50, 'attack' => 4, 'intelligence' => 3, 'dexterity' => 6, 'wisdom' => 2, 'range' => 1, 'bio' => 'A shadow that is gone before you notice it.'],
    ],

    'names' => ['Alatar', 'Brenna', 'Cedric', 'Dara', 'Eldrin', 'Fenna', 'Gorak', 'Hilda', 'Ivor', 'Jaska', 'Kael', 'Lyra', 'Mordin', 'Nyx', 'Orin', 'Pella'],

    // Legend: . floor   # wall   b bush   c chest
    'maps' => [
        'forest' => ['label' => 'Forest', 'grid' => [
            '....#.....', '....#.....', '...c#.....', '.bb##b....', '..........',
            '..........', '....b..bb.', '......#..c', '......#...', '......#...']],
        'valor' => ['label' => 'Valor', 'grid' => [
            '..........', '.##....##.', '.#......#.', '....cc....', '...b..b...',
            '...b..b...', '....cc....', '.#......#.', '.##....##.', '..........']],
        'colosseum' => ['label' => 'Colosseum', 'grid' => [
            '..........', '..........', '..##..##..', '..#....#..', '....cc....',
            '....cc....', '..#....#..', '..##..##..', '..........', '..........']],
    ],

    // Spawn points [x, y] per team
    'spawns' => [
        'A' => [[0, 0], [0, 1]],
        'B' => [[9, 9], [9, 8]],
    ],
];
