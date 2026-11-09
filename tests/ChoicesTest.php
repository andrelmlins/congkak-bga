<?php

/**
 * Tests of the houses a player may choose (see modules/php/Rules/Choices.php, docs/RULES.md, sections 5 and 6). Run with `php tests/ChoicesTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/Rules/Choices.php';

use Bga\Games\congkak\Rules\Choices;

$locations = fn(array $choices) => array_map(fn($c) => $c['location'], $choices);
$houses = board(['1' => [0, 3, 0, 1, 0, 0, 2], '2' => [7, 7, 7, 7, 7, 7, 7]]);

// Alone: any kampong with seeds, or the house to go on from.
checkSame('free: the kampongs with seeds', ['kampong_2', 'kampong_4', 'kampong_7'], $locations(Choices::single('1', $houses, null, null)));
checkSame('free when the next house is the initial one', ['kampong_2', 'kampong_4', 'kampong_7'], $locations(Choices::single('1', $houses, 'initial', null)));
$forced = Choices::single('1', $houses, 'kampong_5', '2');
checkSame('forced: only that house', ['kampong_5'], $locations($forced));
checkSame('forced: of the owner of the house', '2', $forced[0]['playerId']);
check('forced: marked as next', $forced[0]['isNext'] === true);
check('free: not marked as next', Choices::single('1', $houses, null, null)[0]['isNext'] === false);

// Both at once: the same, minus the house the opponent is going on from, minus the burnt ones.
checkSame('both free: the kampongs with seeds', ['kampong_2', 'kampong_4', 'kampong_7'], $locations(Choices::simultaneous('1', $houses, [], null, null, null, null)));
checkSame('the opponent goes on from my house: I cannot take it', ['kampong_2', 'kampong_7'], $locations(Choices::simultaneous('1', $houses, [], null, null, 'kampong_4', '1')));
checkSame('the opponent goes on from their own house: no change', ['kampong_2', 'kampong_4', 'kampong_7'], $locations(Choices::simultaneous('1', $houses, [], null, null, 'kampong_4', '2')));
checkSame('a burnt kampong is not offered', ['kampong_2', 'kampong_7'], $locations(Choices::simultaneous('1', $houses, ['1' => ['kampong_4' => true]], null, null, null, null)));
checkSame('forced in both: only the house to go on from', ['kampong_6'], $locations(Choices::simultaneous('1', $houses, [], 'kampong_6', '2', 'kampong_4', '1')));

finish('ChoicesTest');
