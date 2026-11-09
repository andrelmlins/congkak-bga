<?php

/**
 * Tests of the score of each victory mode (see modules/php/Rules/Scoring.php, docs/RULES.md, section 10). Run with `php tests/ScoringTest.php`.
 */

declare(strict_types=1);

require __DIR__ . '/Check.php';
require __DIR__ . '/../modules/php/Rules/Board.php';
require __DIR__ . '/../modules/php/entities/VictoryMode.php';
require __DIR__ . '/../modules/php/Rules/Scoring.php';

use Bga\Games\congkak\entities\VictoryMode;
use Bga\Games\congkak\Rules\Scoring;

// The score of each victory mode: the highest wins.
checkSame('score by rounds: the round wins', 2, Scoring::score(VictoryMode::NumberOfRounds, 40, 2, 3));
checkSame('score by seeds: the rumah', 40, Scoring::score(VictoryMode::NumberOfSeeds, 40, 2, 3));
checkSame('score by burnt houses: 7 minus the burnt', 4, Scoring::score(VictoryMode::NumberHousesBurned, 40, 2, 3));
checkSame('score by burnt houses: all burnt is 0', 0, Scoring::score(VictoryMode::NumberHousesBurned, 0, 0, 7));
checkSame('score by burnt houses: none burnt is 7', 7, Scoring::score(VictoryMode::NumberHousesBurned, 0, 0, 0));

finish('ScoringTest');
