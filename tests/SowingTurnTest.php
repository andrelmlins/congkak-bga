<?php

/**
 * Tests of the seeds going round the board (see modules/php/Rules/SowingTurn.php, docs/RULES.md, sections 4 to 6). Run with `php tests/SowingTurnTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/SowingPath.php';
require __DIR__ . '/../modules/php/Rules/Sowing.php';
require __DIR__ . '/../modules/php/Rules/SowingEnding.php';
require __DIR__ . '/../modules/php/Rules/SowingTurn.php';

use Bga\Games\congkak\Rules\SowingTurn;

$opponentOf = ['1' => '2', '2' => '1'];
$total = fn(array $h) => array_sum($h['1']['kampong']) + array_sum($h['2']['kampong']) + $h['1']['rumah'] + $h['2']['rumah'];

// 3 seeds from the kampong 3 of player 1: kampong 2, kampong 1 and the rumah. The last seed fell in the rumah, so the player goes on.
$houses = board(['1' => [1, 1, 3, 1, 1, 1, 1], '2' => [1, 1, 1, 1, 1, 1, 1]]);
$result = SowingTurn::resolve($houses, [], $opponentOf, ['1' => ['location' => 'kampong_3', 'playerId' => '1']]);
checkSame('the seeds taken', 3, $result['maxSeeds']);
checkSame('the kampong 3 is empty', 0, $result['houses']['1']['kampong'][3]);
checkSame('the kampong 2 got one', 2, $result['houses']['1']['kampong'][2]);
checkSame('the kampong 1 got one', 2, $result['houses']['1']['kampong'][1]);
checkSame('the rumah got one', 1, $result['houses']['1']['rumah']);
checkSame('the houses of the seeds, in order', ['kampong_2', 'kampong_1', 'rumah'], array_map(fn($m) => $m['location'], $result['movements']['1']));
check('ending in the own rumah: goes on', $result['endings']['1']['active'] === true);
checkSame('no seed is created or lost', $total($houses), $total($result['houses']));

// The picked house is also the start of the seeds: nothing is left in it.
$houses = board(['1' => [0, 0, 0, 0, 0, 0, 2], '2' => [4, 4, 4, 4, 4, 4, 4]]);
$result = SowingTurn::resolve($houses, [], $opponentOf, ['1' => ['location' => 'kampong_7', 'playerId' => '1']]);
checkSame('the first house is emptied', 0, $result['houses']['1']['kampong'][7]);
checkSame('the first seed falls in the next house', 1, $result['houses']['1']['kampong'][6]);

finish('SowingTurnTest');
