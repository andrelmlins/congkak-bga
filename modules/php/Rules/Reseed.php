<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * Refilling a player's kampongs from the rumah at the start of the next round (docs/RULES.md, section 8).
 */
final class Reseed
{
    /**
     * Fills the kampongs from 1 to 7 with up to 7 seeds each. A kampong that gets some but less than 7 stays in the game. The kampongs after the
     * seeds ran out are the ones to burn. When a kampong that was already burnt is reached, the seeds still in the rumah leave the game.
     *
     * @param int $rumah the seeds in the player's rumah
     * @param array<string, bool> $locked `[location => bool]` of the player's kampongs that are already burnt
     * @return array{movements: array<string, int>, lockeds: string[], rumah: int, outward: int} seeds put in each kampong, the locations that ran out
     *         of seeds, the seeds left in the rumah, and the seeds that left the game
     */
    public static function compute(int $rumah, array $locked): array
    {
        $total = $rumah;
        $lockeds = [];
        $movements = [];
        $outward = 0;

        for ($i = 1; $i <= Board::KAMPONGS; $i++) {
            $location = "kampong_{$i}";

            if (!empty($locked[$location])) {
                $outward = $total;
                $total = 0;
                break;
            }

            if ($total == 0) {
                $lockeds[] = $location;
                continue;
            }

            $count = min($total, Board::SEEDS_PER_KAMPONG);

            $total -= $count;
            $movements[$location] = $count;
        }

        return ['movements' => $movements, 'lockeds' => $lockeds, 'rumah' => $total, 'outward' => $outward];
    }
}
