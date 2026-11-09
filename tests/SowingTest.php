<?php

/**
 * Tests of the seeds of one or both players going round the board (see modules/php/Rules/Sowing.php, docs/RULES.md, sections 4 and 5).
 * Run with `php tests/SowingTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/SowingPath.php';
require __DIR__ . '/../modules/php/Rules/Sowing.php';

use Bga\Games\congkak\Rules\Sowing;

$opponentOf = ['1' => '2', '2' => '1'];

// 9 seeds from the kampong 7 of player 1: the own kampongs 6 to 1, the rumah, then the kampongs 7 and 6 of the opponent.
$houses = board(['1' => [0, 0, 0, 0, 0, 0, 9], '2' => [0, 0, 0, 0, 0, 0, 0]]);
$initial = ['1' => ['location' => 'kampong_7', 'playerId' => '1', 'seeds' => 9]];
[$after, $final, $movements] = Sowing::sow($houses, [], $opponentOf, $initial, 9);

checkSame('where the last seed fell', ['location' => 'kampong_6', 'playerId' => '2'], ['location' => $final['1']['location'], 'playerId' => $final['1']['playerId']]);
checkSame('the path of the 9 seeds', ['kampong_6', 'kampong_5', 'kampong_4', 'kampong_3', 'kampong_2', 'kampong_1', 'rumah', 'kampong_7', 'kampong_6'], array_map(fn($m) => $m['location'], $movements['1']));
checkSame('the rumah got one', 1, $after['1']['rumah']);
checkSame('the opponent rumah got none', 0, $after['2']['rumah']);

// A burnt kampong is skipped, so the seed goes to the next one.
[$after] = Sowing::sow(board(['1' => [0, 2, 0, 0, 0, 0, 0], '2' => [0, 0, 0, 0, 0, 0, 0]]), ['1' => ['kampong_1' => true]], $opponentOf, ['1' => ['location' => 'kampong_2', 'playerId' => '1', 'seeds' => 2]], 2);
checkSame('the burnt kampong got nothing', 0, $after['1']['kampong'][1]);
checkSame('the rumah got the seed instead', 1, $after['1']['rumah']);

finish('SowingTest');
