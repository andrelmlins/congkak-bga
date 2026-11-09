<?php

/**
 * Tests of the end of a round, who starts the next one, the end of the game and the scores
 * (see modules/php/Rules/RoundResult.php, GameEnd.php, Scoring.php, docs/RULES.md sections 7 to 10). Run with `php tests/RoundResultTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/entities/VictoryMode.php';
require __DIR__ . '/../modules/php/Rules/RoundResult.php';
require __DIR__ . '/../modules/php/Rules/GameEnd.php';
require __DIR__ . '/../modules/php/Rules/Scoring.php';

use Bga\Games\congkak\entities\VictoryMode;
use Bga\Games\congkak\Rules\GameEnd;
use Bga\Games\congkak\Rules\RoundResult;
use Bga\Games\congkak\Rules\Scoring;

// The round ends when one side has no seed left in its kampongs.
check('a side is empty: the round is over', RoundResult::isOver(['1' => 12, '2' => 0]) === true);
check('both sides have seeds: it goes on', RoundResult::isOver(['1' => 3, '2' => 4]) === false);

// The winner of a round has more seeds in the rumah. A tie gives a win to both (decided 2026-11-09).
checkSame('the most seeds wins', ['1'], RoundResult::winners(['1' => 60, '2' => 38]));
checkSame('the other one wins', ['2'], RoundResult::winners(['1' => 20, '2' => 78]));
checkSame('a tie: both win', ['1', '2'], RoundResult::winners(['1' => 49, '2' => 49]));
checkSame('0 to 0 is a tie too', ['1', '2'], RoundResult::winners(['1' => 0, '2' => 0]));
checkSame('0 against something: that one wins', ['2'], RoundResult::winners(['1' => 0, '2' => 5]));

// The last round: the end of the rounds, or somebody has more than half of the wins. A game with no limit never ends here.
check('one single round: the first one is the last', RoundResult::isLast(1, 1, 0) === true);
check('3 rounds, the first one: goes on', RoundResult::isLast(3, 1, 1) === false);
check('3 rounds, somebody has 2 wins: over', RoundResult::isLast(3, 2, 2) === true);
check('3 rounds, the third one: over', RoundResult::isLast(3, 3, 1) === true);
check('5 rounds, 3 wins: over', RoundResult::isLast(5, 3, 3) === true);
check('5 rounds, 2 wins: goes on', RoundResult::isLast(5, 3, 2) === false);
check('7 rounds, 4 wins: over', RoundResult::isLast(7, 4, 4) === true);
check('no limit (total burn): never over by the rounds', RoundResult::isLast(null, 99, 99) === false);

// Who starts the next round: the one with fewer seeds in all, and on a tie the first player of the table (decided 2026-11-09).
$playerNo = ['1' => 1, '2' => 2];
checkSame('the one with fewer seeds starts', '2', RoundResult::firstPlayer(['1' => 50, '2' => 40], $playerNo));
checkSame('the other one with fewer seeds starts', '1', RoundResult::firstPlayer(['1' => 40, '2' => 50], $playerNo));
checkSame('a tie: player 1 of the table', '1', RoundResult::firstPlayer(['1' => 45, '2' => 45], $playerNo));
checkSame('a tie, listed the other way round: still player 1 of the table', '1', RoundResult::firstPlayer(['2' => 45, '1' => 45], $playerNo));
checkSame('a tie when the ids are swapped at the table', '2', RoundResult::firstPlayer(['1' => 45, '2' => 45], ['1' => 2, '2' => 1]));

// A player out of kampongs: 7 burnt, or 6 burnt and the seventh with one single seed (decided 2026-11-09).
check('7 burnt: out', GameEnd::playerOut(7, 0) === true);
check('6 burnt and the last one with 1 seed: out', GameEnd::playerOut(6, 1) === true);
check('6 burnt and the last one with 2 seeds: still in', GameEnd::playerOut(6, 2) === false);
check('6 burnt and the last one empty: not out by this rule', GameEnd::playerOut(6, 0) === false);
check('5 burnt and 1 seed: still in', GameEnd::playerOut(5, 1) === false);
check('nothing burnt: in', GameEnd::playerOut(0, 49) === false);
check('the game ends when any player is out', GameEnd::isOver(['1' => ['locked' => 2, 'seeds' => 30], '2' => ['locked' => 7, 'seeds' => 0]]) === true);
check('the game goes on when nobody is out', GameEnd::isOver(['1' => ['locked' => 2, 'seeds' => 30], '2' => ['locked' => 3, 'seeds' => 20]]) === false);

// The score of each victory mode: the highest wins.
checkSame('score by rounds: the round wins', 2, Scoring::score(VictoryMode::NumberOfRounds, 40, 2, 3));
checkSame('score by seeds: the rumah', 40, Scoring::score(VictoryMode::NumberOfSeeds, 40, 2, 3));
checkSame('score by burnt houses: 7 minus the burnt', 4, Scoring::score(VictoryMode::NumberHousesBurned, 40, 2, 3));
checkSame('score by burnt houses: all burnt is 0', 0, Scoring::score(VictoryMode::NumberHousesBurned, 0, 0, 7));
checkSame('score by burnt houses: none burnt is 7', 7, Scoring::score(VictoryMode::NumberHousesBurned, 0, 0, 0));

finish('RoundResultTest');
