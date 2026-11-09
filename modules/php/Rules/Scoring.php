<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

use Bga\Games\congkak\entities\VictoryMode;

/**
 * The score of a player in each victory mode (docs/RULES.md, section 10): the highest wins.
 */
final class Scoring
{
    public static function score(VictoryMode $mode, int $rumah, int $roundWins, int $lockedCount): int
    {
        return match ($mode) {
            VictoryMode::NumberOfSeeds => $rumah,
            VictoryMode::NumberOfRounds => $roundWins,
            VictoryMode::NumberHousesBurned => Board::KAMPONGS - $lockedCount,
        };
    }
}
