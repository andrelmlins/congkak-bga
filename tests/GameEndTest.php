<?php

/**
 * Tests of when a player is out of kampongs and the game ends (see modules/php/Rules/GameEnd.php, docs/RULES.md, section 9). Run with `php tests/GameEndTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/GameEnd.php';

use Bga\Games\congkak\Rules\GameEnd;

// A player out of kampongs: 7 burnt, or 6 burnt and the seventh with one single seed (decided 2026-11-09).
check('7 burnt: out', GameEnd::playerOut(7, 0) === true);
check('6 burnt and the last one with 1 seed: out', GameEnd::playerOut(6, 1) === true);
check('6 burnt and the last one with 2 seeds: still in', GameEnd::playerOut(6, 2) === false);
check('6 burnt and the last one empty: not out by this rule', GameEnd::playerOut(6, 0) === false);
check('5 burnt and 1 seed: still in', GameEnd::playerOut(5, 1) === false);
check('nothing burnt: in', GameEnd::playerOut(0, 49) === false);
check('the game ends when any player is out', GameEnd::isOver(['1' => ['locked' => 2, 'seeds' => 30], '2' => ['locked' => 7, 'seeds' => 0]]) === true);
check('the game goes on when nobody is out', GameEnd::isOver(['1' => ['locked' => 2, 'seeds' => 30], '2' => ['locked' => 3, 'seeds' => 20]]) === false);

finish('GameEndTest');
