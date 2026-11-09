<?php

/**
 * Tests of the way the seeds travel (see modules/php/Rules/SowingPath.php and Board.php, docs/RULES.md section 4). Run with `php tests/SowingPathTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/SowingPath.php';

use Bga\Games\congkak\Rules\Board;
use Bga\Games\congkak\Rules\SowingPath;

// The board: 7 seeds in each of the 14 kampongs, 98 in all, the rumahs empty.
$houses = Board::initial(['1', '2']);
check('98 seeds at the start', array_sum($houses['1']['kampong']) + array_sum($houses['2']['kampong']) === 98);
check('the rumahs start empty', $houses['1']['rumah'] === 0 && $houses['2']['rumah'] === 0);
check('kampong 3 is opposite to kampong 5', Board::opposite(3) === 5);
check('kampong 1 is opposite to kampong 7', Board::opposite(1) === 7 && Board::opposite(7) === 1);
check('kampong 4 is opposite to itself', Board::opposite(4) === 4);
check('the number of a kampong location', Board::houseNumber('kampong_6') === 6);

// The circuit of a player: own kampongs 7 to 1, own rumah, opponent's kampongs 7 to 1, opponent's rumah.
$sequence = SowingPath::sequence('1', '2');
check('the circuit has 16 houses', count($sequence) === 16);
check('it starts in the own kampong 7', $sequence[0] == ['playerId' => '1', 'location' => 'kampong_7']);
check('the own rumah comes after kampong 1', $sequence[7] == ['playerId' => '1', 'location' => 'rumah']);
check('the opponent starts in their kampong 7', $sequence[8] == ['playerId' => '2', 'location' => 'kampong_7']);

// Clockwise, with no burnt house.
$none = [];
checkSame('own kampong 1 goes to the own rumah', ['playerId' => '1', 'location' => 'rumah'], SowingPath::next('1', '2', '1', 'kampong_1', $none));
checkSame('own kampong 7 goes to kampong 6', ['playerId' => '1', 'location' => 'kampong_6'], SowingPath::next('1', '2', '1', 'kampong_7', $none));
checkSame('the own rumah goes to the opponent kampong 7', ['playerId' => '2', 'location' => 'kampong_7'], SowingPath::next('1', '2', '1', 'rumah', $none));

// The opponent's rumah is skipped, so after their kampong 1 comes the own kampong 7.
checkSame('the opponent rumah is skipped', ['playerId' => '1', 'location' => 'kampong_7'], SowingPath::next('1', '2', '2', 'kampong_1', $none));
// And seen from the other player, the same board goes the other way round.
checkSame('seen from the other player: own rumah', ['playerId' => '2', 'location' => 'rumah'], SowingPath::next('2', '1', '2', 'kampong_1', $none));
checkSame('the other player skips my rumah', ['playerId' => '2', 'location' => 'kampong_7'], SowingPath::next('2', '1', '1', 'kampong_1', $none));

// Burnt houses are skipped (docs/RULES.md section 8).
$locked = ['2' => ['kampong_7' => true, 'kampong_6' => true]];
checkSame('a burnt house is skipped', ['playerId' => '2', 'location' => 'kampong_5'], SowingPath::next('1', '2', '1', 'rumah', $locked));
$locked = ['1' => ['kampong_6' => true]];
checkSame('own burnt house is skipped', ['playerId' => '1', 'location' => 'kampong_5'], SowingPath::next('1', '2', '1', 'kampong_7', $locked));

// Ids that come as numbers or as text mean the same player.
checkSame('ids as numbers', ['playerId' => '1', 'location' => 'rumah'], SowingPath::next(1, 2, 1, 'kampong_1', $none));

// A circuit of nothing but burnt houses cannot be walked: it must not loop forever.
$all = ['1' => [], '2' => []];
foreach ([1, 2] as $player) {
    for ($i = 1; $i <= 7; $i++) {
        $all[(string) $player]["kampong_{$i}"] = true;
    }
    $all[(string) $player]['rumah'] = true;
}
$threw = false;
try {
    SowingPath::next('1', '2', '1', 'kampong_7', $all);
} catch (\LogicException) {
    $threw = true;
}
check('a circuit of only burnt houses throws', $threw);

finish('SowingPathTest');
