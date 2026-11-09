<?php

/**
 * Tests of the refilling of the kampongs for the next round (see modules/php/Rules/Reseed.php, docs/RULES.md section 8).
 * Run with `php tests/ReseedTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/Reseed.php';

use Bga\Games\congkak\Rules\Reseed;

/** The seeds the result puts in the kampongs, plus what stays in the rumah and what leaves: always the rumah that came in. */
$conserved = static fn(array $r): int => array_sum($r['movements']) + $r['rumah'] + $r['outward'];

// 49 or more: every kampong gets 7, the rest stays in the rumah. Nothing burns.
$r = Reseed::compute(60, []);
checkSame('60 seeds: 7 in each kampong', array_fill_keys(['kampong_1', 'kampong_2', 'kampong_3', 'kampong_4', 'kampong_5', 'kampong_6', 'kampong_7'], 7), $r['movements']);
checkSame('60 seeds: 11 stay in the rumah', 11, $r['rumah']);
checkSame('60 seeds: nothing burns', [], $r['lockeds']);
checkSame('60 seeds: nothing leaves the game', 0, $r['outward']);

// Not enough for all: a kampong with less than 7 stays in the game, the ones after the seeds ran out burn.
$r = Reseed::compute(20, []);
checkSame('20 seeds: 7, 7 and 6', ['kampong_1' => 7, 'kampong_2' => 7, 'kampong_3' => 6], $r['movements']);
checkSame('20 seeds: kampongs 4 to 7 burn', ['kampong_4', 'kampong_5', 'kampong_6', 'kampong_7'], $r['lockeds']);
checkSame('20 seeds: the rumah is empty', 0, $r['rumah']);

$r = Reseed::compute(7, []);
checkSame('7 seeds: kampong 1 gets all of them', ['kampong_1' => 7], $r['movements']);
checkSame('7 seeds: six kampongs burn', 6, count($r['lockeds']));

$r = Reseed::compute(1, []);
checkSame('1 seed: kampong 1 gets 1 and stays in the game', ['kampong_1' => 1], $r['movements']);

$r = Reseed::compute(0, []);
checkSame('no seed: every kampong burns', 7, count($r['lockeds']));
checkSame('no seed: nothing is placed', [], $r['movements']);

// A kampong that was already burnt: the seeds still in the rumah when it is reached leave the game (decided 2026-11-09).
$burnt = ['kampong_6' => true, 'kampong_7' => true];

$r = Reseed::compute(40, $burnt);
checkSame('40 seeds, 6 and 7 burnt: 7 in each of 1 to 5', ['kampong_1' => 7, 'kampong_2' => 7, 'kampong_3' => 7, 'kampong_4' => 7, 'kampong_5' => 7], $r['movements']);
checkSame('40 seeds, 6 and 7 burnt: the 5 left leave the game', 5, $r['outward']);
checkSame('40 seeds, 6 and 7 burnt: the rumah is empty', 0, $r['rumah']);
checkSame('40 seeds, 6 and 7 burnt: nothing new burns', [], $r['lockeds']);

$r = Reseed::compute(30, $burnt);
checkSame('30 seeds, 6 and 7 burnt: kampong 5 gets the last 2', 2, $r['movements']['kampong_5']);
checkSame('30 seeds, 6 and 7 burnt: nothing leaves', 0, $r['outward']);

// Seeds are never lost or made up.
foreach ([0, 1, 6, 7, 8, 20, 30, 49, 50, 60, 98] as $rumah) {
    checkSame("conservation with $rumah in the rumah", $rumah, $conserved(Reseed::compute($rumah, [])));
    checkSame("conservation with $rumah in the rumah and two kampongs burnt", $rumah, $conserved(Reseed::compute($rumah, $burnt)));
}

finish('ReseedTest');
