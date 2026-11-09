<?php

/**
 * Tests of the board helpers (see modules/php/Rules/Board.php, docs/RULES.md, section 1). Run with `php tests/BoardTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';

use Bga\Games\congkak\Rules\Board;

$houses = Board::initial(['1', '2']);
checkSame('seven kampongs per player', 7, count($houses['1']['kampong']));
checkSame('seven seeds in each kampong', 7, Board::seeds($houses, '1', 'kampong_4'));
checkSame('the rumah starts empty', 0, Board::seeds($houses, '2', 'rumah'));
checkSame('98 seeds in all', 98, array_sum($houses['1']['kampong']) + array_sum($houses['2']['kampong']));

checkSame('the number of a kampong', 5, Board::houseNumber('kampong_5'));
checkSame('the house in front of the 1 is the 7', 7, Board::opposite(1));
checkSame('the house in front of the 3 is the 5', 5, Board::opposite(3));
checkSame('the 4 faces itself', 4, Board::opposite(4));

Board::add($houses, '1', 'kampong_1', 2);
checkSame('add changes the seeds', 9, Board::seeds($houses, '1', 'kampong_1'));
Board::set($houses, '1', 'rumah', 5);
checkSame('set changes the rumah', 5, Board::seeds($houses, '1', 'rumah'));

$locked = ['1' => ['kampong_2' => true, 'kampong_3' => false]];
check('a burnt kampong is locked', Board::isLocked($locked, '1', 'kampong_2') === true);
check('an open kampong is not', Board::isLocked($locked, '1', 'kampong_3') === false);
check('a player with no burnt kampong is not locked', Board::isLocked($locked, '2', 'kampong_2') === false);

finish('BoardTest');
