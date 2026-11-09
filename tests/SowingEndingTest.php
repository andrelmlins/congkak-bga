<?php

/**
 * Tests of how a sowing ends and of a whole turn (see modules/php/Rules/SowingEnding.php, SowingTurn.php, docs/RULES.md section 6).
 * Run with `php tests/SowingEndingTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/SowingPath.php';
require __DIR__ . '/../modules/php/Rules/Sowing.php';
require __DIR__ . '/../modules/php/Rules/SowingEnding.php';
require __DIR__ . '/../modules/php/Rules/SowingTurn.php';

use Bga\Games\congkak\Rules\SowingEnding;
use Bga\Games\congkak\Rules\SowingTurn;

$opponentOf = ['1' => '2', '2' => '1'];
$none = [];
$empty = [0, 0, 0, 0, 0, 0, 0];

function turn(array $houses, array $opponentOf, array $locked, array $picks): array
{
    return SowingTurn::resolve($houses, $locked, $opponentOf, $picks);
}

// 1. The last seed in the own rumah: plays again, free choice.
$houses = board(['1' => [1, 0, 0, 0, 0, 0, 0], '2' => [3, 0, 0, 0, 0, 0, 0]]);
$result = turn($houses, $opponentOf, $none, ['1' => ['location' => 'kampong_1', 'playerId' => '1']]);
$ending = $result['endings']['1'];
check('rumah: the type', $ending['type'] === SowingEnding::RUMAH);
check('rumah: goes on', $ending['active'] === true);
checkSame('rumah: free choice', ['location' => 'initial', 'playerId' => ''], $ending['next']);
check('rumah: the seed is in the rumah', $result['houses']['1']['rumah'] === 1 && $result['houses']['1']['kampong'][1] === 0);

// 2. The last seed in a house that already had seeds: picks them up and goes on from there, either side.
$houses = board(['1' => [2, 0, 0, 0, 0, 0, 0], '2' => [0, 0, 0, 0, 0, 0, 3]]);
$result = turn($houses, $opponentOf, $none, ['1' => ['location' => 'kampong_1', 'playerId' => '1']]);
$ending = $result['endings']['1'];
check('occupied (opponent side): the type', $ending['type'] === SowingEnding::OCCUPIED);
checkSame('occupied: goes on from that house', ['location' => 'kampong_7', 'playerId' => '2'], $ending['next']);
check('occupied: nothing is captured', !isset($ending['capture']));

$houses = board(['1' => [0, 0, 0, 0, 0, 3, 1], '2' => $empty]);
$result = turn($houses, $opponentOf, $none, ['1' => ['location' => 'kampong_7', 'playerId' => '1']]);
check('occupied (own side): the type', $result['endings']['1']['type'] === SowingEnding::OCCUPIED);
checkSame('occupied (own side): goes on from kampong 6', ['location' => 'kampong_6', 'playerId' => '1'], $result['endings']['1']['next']);

// 3. The last seed in an empty house of their own: captures that seed and the opposite house. The turn ends.
$houses = board(['1' => [0, 0, 0, 0, 2, 0, 0], '2' => [0, 0, 0, 0, 4, 0, 0]]);
$result = turn($houses, $opponentOf, $none, ['1' => ['location' => 'kampong_5', 'playerId' => '1']]);
$ending = $result['endings']['1'];
check('capture: the type', $ending['type'] === SowingEnding::CAPTURE);
check('capture: the turn ends', $ending['active'] === false);
checkSame('capture: 1 seed plus the 4 of the opposite house', 5, $ending['capture']['total']);
checkSame('capture: the opposite house', 'kampong_5', $ending['capture']['opponentLocation']);
check('capture: the rumah gets 5', $result['houses']['1']['rumah'] === 5);
check('capture: both houses are emptied', $result['houses']['1']['kampong'][3] === 0 && $result['houses']['2']['kampong'][5] === 0);
check('capture: the seed that only passed stays', $result['houses']['1']['kampong'][4] === 1);

// 3b. With the opposite house empty, only the own seed goes to the rumah (decided 2026-11-09).
$houses = board(['1' => [0, 0, 0, 0, 2, 0, 0], '2' => $empty]);
$result = turn($houses, $opponentOf, $none, ['1' => ['location' => 'kampong_5', 'playerId' => '1']]);
checkSame('capture with an empty opposite house', 1, $result['endings']['1']['capture']['total']);
check('capture with an empty opposite house: the rumah gets 1', $result['houses']['1']['rumah'] === 1);

// 4. The last seed in an empty house of the opponent: nothing is captured. The turn ends.
$houses = board(['1' => [2, 0, 0, 0, 0, 0, 0], '2' => [0, 0, 0, 0, 0, 0, 0]]);
$result = turn($houses, $opponentOf, $none, ['1' => ['location' => 'kampong_1', 'playerId' => '1']]);
$ending = $result['endings']['1'];
check('opponent: the type', $ending['type'] === SowingEnding::OPPONENT);
check('opponent: the turn ends', $ending['active'] === false);
check('opponent: the seed stays there', $result['houses']['2']['kampong'][7] === 1 && $result['houses']['1']['rumah'] === 1);
check('opponent: no capture', !isset($ending['capture']));

// Seeds are never lost or made up.
$total = static fn(array $h): int => array_sum($h['1']['kampong']) + array_sum($h['2']['kampong']) + $h['1']['rumah'] + $h['2']['rumah'];
check('the seeds of a turn are conserved', $total($result['houses']) === $total($houses));

// A burnt house takes no seed: the sowing jumps over it.
$houses = board(['1' => [0, 0, 0, 0, 0, 0, 3], '2' => [0, 0, 0, 0, 0, 0, 0]]);
$locked = ['1' => ['kampong_6' => true]];
$result = turn($houses, $opponentOf, $locked, ['1' => ['location' => 'kampong_7', 'playerId' => '1']]);
check('a burnt house gets no seed', $result['houses']['1']['kampong'][6] === 0);
check('the seeds went to 5, 4 and 3', $result['houses']['1']['kampong'][5] === 1 && $result['houses']['1']['kampong'][4] === 1);
check('the last one fell in the empty kampong 3 and was captured', $result['endings']['1']['type'] === SowingEnding::CAPTURE && $result['houses']['1']['kampong'][3] === 0 && $result['houses']['1']['rumah'] === 1);

// The movements list every house a seed went to, in order, per player.
checkSame('movements', [['playerId' => '1', 'location' => 'kampong_5'], ['playerId' => '1', 'location' => 'kampong_4'], ['playerId' => '1', 'location' => 'kampong_3']], $result['movements']['1']);
checkSame('what was taken', ['location' => 'kampong_7', 'playerId' => '1', 'seeds' => 3], $result['initialHouse']['1']);
checkSame('the most seeds of any player', 3, $result['maxSeeds']);

finish('SowingEndingTest');
