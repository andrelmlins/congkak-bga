<?php

/**
 * Tests of the simultaneous opening (see modules/php/Rules/SowingTurn.php, Choices.php, Opening.php, docs/RULES.md section 5).
 * Run with `php tests/OpeningTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/SowingPath.php';
require __DIR__ . '/../modules/php/Rules/Sowing.php';
require __DIR__ . '/../modules/php/Rules/SowingEnding.php';
require __DIR__ . '/../modules/php/Rules/SowingTurn.php';
require __DIR__ . '/../modules/php/Rules/Choices.php';
require __DIR__ . '/../modules/php/Rules/Opening.php';

use Bga\Games\congkak\Rules\Board;
use Bga\Games\congkak\Rules\Choices;
use Bga\Games\congkak\Rules\Opening;
use Bga\Games\congkak\Rules\SowingTurn;

$opponentOf = ['1' => '2', '2' => '1'];
$none = [];
$total = static fn(array $h): int => array_sum($h['1']['kampong']) + array_sum($h['2']['kampong']) + $h['1']['rumah'] + $h['2']['rumah'];

// Every pair of first picks (49): at least one player always goes on. Nobody is ever left without a turn in the first opening, and the
// seeds are conserved (docs/RULES.md section 5; the same result as an independent simulation done on 2026-11-09).
$both = 0;
$one = 0;
$nobody = 0;
$lostSeeds = 0;
$onePairs = [];

for ($a = 1; $a <= 7; $a++) {
    for ($b = 1; $b <= 7; $b++) {
        $houses = Board::initial(['1', '2']);
        $result = SowingTurn::resolve($houses, $none, $opponentOf, [
            '1' => ['location' => "kampong_{$a}", 'playerId' => '1'],
            '2' => ['location' => "kampong_{$b}", 'playerId' => '2'],
        ]);

        $actives = array_keys(array_filter($result['endings'], static fn(array $e): bool => $e['active']));

        if (count($actives) === 2) {
            $both++;
        } elseif (count($actives) === 1) {
            $one++;
            $onePairs[] = "$a,$b";
        } else {
            $nobody++;
        }

        if ($total($result['houses']) !== 98) {
            $lostSeeds++;
        }
    }
}

checkSame('pairs where both go on', 37, $both);
checkSame('pairs where only one goes on', 12, $one);
checkSame('pairs where nobody goes on', 0, $nobody);
checkSame('no pair loses or makes up a seed', 0, $lostSeeds);

// The one-active pairs are exactly (n, n + 1) and (n + 1, n): the last seed falls in the empty house the other one took from.
$expected = [];
for ($n = 1; $n <= 6; $n++) {
    $expected[] = "$n," . ($n + 1);
    $expected[] = ($n + 1) . ",$n";
}
sort($expected);
sort($onePairs);
checkSame('which pairs leave only one player going on', $expected, $onePairs);

// The two sowings run one seed per player and per step, so each player's seeds go the same way as if alone when the paths do not cross.
$houses = Board::initial(['1', '2']);
$result = SowingTurn::resolve($houses, $none, $opponentOf, [
    '1' => ['location' => 'kampong_1', 'playerId' => '1'],
    '2' => ['location' => 'kampong_1', 'playerId' => '2'],
]);
checkSame('both took 7 seeds', 7, $result['maxSeeds']);
checkSame('each player has 7 movements', [7, 7], [count($result['movements']['1']), count($result['movements']['2'])]);
check('both ended in the opponent kampong 2, which had seeds', $result['endings']['1']['type'] === 'occupied' && $result['endings']['2']['type'] === 'occupied');

// The second player settles after the first, so it sees what the first one captured (decided 2026-11-09: the table order is the priority).
$houses = board(['1' => [0, 0, 0, 0, 2, 0, 0], '2' => [0, 0, 0, 0, 2, 0, 0]]);
$result = SowingTurn::resolve($houses, $none, $opponentOf, [
    '1' => ['location' => 'kampong_5', 'playerId' => '1'],
    '2' => ['location' => 'kampong_5', 'playerId' => '2'],
]);
check('both captured', $result['endings']['1']['type'] === 'capture' && $result['endings']['2']['type'] === 'capture');
checkSame('the first player captures its own seed and the opposite house, which is empty because the second took the seeds from it', 1, $result['endings']['1']['capture']['total']);

// Who goes on (Opening::next).
check('one goes on: alone', Opening::next(['1'], false) === Opening::SINGLE);
check('nobody goes on: the first player of the table', Opening::next([], false) === Opening::FIRST_PLAYER);
check('both, with different choices: together', Opening::next(['1', '2'], false) === Opening::BOTH);
check('both, forced into the same house: the first player of the table', Opening::next(['1', '2'], true) === Opening::SAME_HOLE);

// What each may choose when both go on at the same time (docs/RULES.md section 5).
$houses = board(['1' => [5, 0, 3, 3, 3, 3, 3], '2' => [4, 4, 4, 4, 4, 4, 4]]);
$locked = ['1' => ['kampong_6' => true]];

checkSame('free choice: only own kampongs with seeds, not the empty one and not the burnt one', ['kampong_1', 'kampong_3', 'kampong_4', 'kampong_5', 'kampong_7'],
    array_column(Choices::simultaneous('1', $houses, $locked, null, null, null, null), 'location'));

checkSame('free choice, "initial" means free too', ['kampong_1', 'kampong_3', 'kampong_4', 'kampong_5', 'kampong_7'],
    array_column(Choices::simultaneous('1', $houses, $locked, 'initial', '', 'initial', ''), 'location'));

checkSame('free choice: not the house the opponent goes on from', ['kampong_1', 'kampong_3', 'kampong_5', 'kampong_7'],
    array_column(Choices::simultaneous('1', $houses, $locked, null, null, 'kampong_4', '1'), 'location'));

checkSame('an opponent house that the other one goes on from takes nothing from my choices', ['kampong_1', 'kampong_3', 'kampong_4', 'kampong_5', 'kampong_7'],
    array_column(Choices::simultaneous('1', $houses, $locked, null, null, 'kampong_4', '2'), 'location'));

$forced = Choices::simultaneous('1', $houses, $locked, 'kampong_3', '2', 'kampong_1', '1');
checkSame('forced: only that house', [['location' => 'kampong_3', 'playerId' => '2', 'isNext' => true]], $forced);
check('free choices are not marked as next', Choices::simultaneous('1', $houses, $locked, null, null, null, null)[0]['isNext'] === false);

// A single player in turn.
checkSame('single, free: every own kampong with seeds', ['kampong_1', 'kampong_3', 'kampong_4', 'kampong_5', 'kampong_6', 'kampong_7'],
    array_column(Choices::single('1', $houses, null, null), 'location'));
checkSame('single, forced', [['location' => 'kampong_2', 'playerId' => '2', 'isNext' => true]], Choices::single('1', $houses, 'kampong_2', '2'));
checkSame('single, "initial" is free', 6, count(Choices::single('1', $houses, 'initial', '')));

finish('OpeningTest');
