<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * How a round ends and who starts the next one (docs/RULES.md, sections 7 to 9).
 */
final class RoundResult
{
    /** @param array<int|string, int> $kampongSeeds per player, the seeds left in their kampongs @return bool whether the round ends: a side has none */
    public static function isOver(array $kampongSeeds): bool
    {
        foreach ($kampongSeeds as $seeds) {
            if ($seeds == 0) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int|string, int> $rumah per player, in table order @return array the players with the most seeds in the rumah: both on a tie */
    public static function winners(array $rumah): array
    {
        $winners = [];
        $max = 0;

        foreach ($rumah as $playerId => $seeds) {
            if ($seeds > $max) {
                $max = $seeds;
                $winners = [$playerId];
            } elseif ($seeds == $max) {
                $winners[] = $playerId;
            }
        }

        return $winners;
    }

    /**
     * @param ?int $total the rounds of the game (null: no limit, "total burn")
     * @param int $current the number of the round that just ended
     * @param int $maxWins the most round wins of any player
     * @return bool whether the game ends with this round: it was the last, or somebody already has more than half of the wins
     */
    public static function isLast(?int $total, int $current, int $maxWins): bool
    {
        if ($total === null) {
            return false;
        }

        return $current == $total || $maxWins > ($total / 2);
    }

    /**
     * @param array<int|string, int> $seeds per player, in table order, the seeds in kampongs and rumah after reseeding
     * @param array<int|string, int> $playerNo per player, the place at the table
     * @return int|string|null who starts the next round: the one with fewer seeds, and on a tie the first player of the table
     */
    public static function firstPlayer(array $seeds, array $playerNo)
    {
        $count = 1000;
        $first = null;

        foreach ($seeds as $playerId => $total) {
            if ($total < $count) {
                $first = $playerId;
                $count = $total;
            } elseif ($total == $count && $playerNo[$playerId] == 1) {
                $first = $playerId;
            }
        }

        return $first;
    }
}
