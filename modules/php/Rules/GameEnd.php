<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * When a player has no kampongs left to play (docs/RULES.md, section 9): all 7 are burnt, or 6 are and the seventh holds one single seed.
 */
final class GameEnd
{
    public static function playerOut(int $lockedCount, int $kampongSeeds): bool
    {
        if ($lockedCount == Board::KAMPONGS) {
            return true;
        }

        return $lockedCount == Board::KAMPONGS - 1 && $kampongSeeds == 1;
    }

    /** @param array<int|string, array{locked: int, seeds: int}> $players per player, the burnt kampongs and the seeds in their kampongs */
    public static function isOver(array $players): bool
    {
        foreach ($players as $player) {
            if (self::playerOut($player['locked'], $player['seeds'])) {
                return true;
            }
        }

        return false;
    }
}
