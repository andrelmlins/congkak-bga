<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * Sowing the seeds taken from one or two houses at the same time, one seed per player and per step (docs/RULES.md, sections 5 and 6).
 */
final class Sowing
{
    /**
     * @param array $houses the board (see Board)
     * @param array $locked `[playerId => [location => bool]]`
     * @param array $opponentOf `[playerId => opponentId]`
     * @param array<int|string, array{location: string, playerId: mixed, seeds: int}> $initialHouse per player, the house the seeds are taken from
     * @return array{0: array, 1: array, 2: array} the board after sowing (the houses taken from are empty), the house where each player's last seed ended
     *                                                (`location`, `playerId` of the owner, `seeds` left: 0), and the houses each seed went to, per player and in order
     */
    public static function sow(array $houses, array $locked, array $opponentOf, array $initialHouse, int $maxSeeds): array
    {
        foreach ($initialHouse as $house) {
            Board::set($houses, $house['playerId'], $house['location'], 0);
        }

        $movements = [];
        $finalHouse = $initialHouse;

        for ($i = 0; $i < $maxSeeds; $i++) {
            foreach ($finalHouse as $playerId => $house) {
                if ($house['seeds'] == 0) {
                    continue;
                }

                $next = SowingPath::next($playerId, $opponentOf[$playerId], $house['playerId'], $house['location'], $locked);

                $movements[$playerId][] = $next;
                Board::add($houses, $next['playerId'], $next['location'], 1);

                $finalHouse[$playerId] = [
                    'location' => $next['location'],
                    'playerId' => $next['playerId'],
                    'seeds' => $house['seeds'] - 1,
                ];
            }
        }

        return [$houses, $finalHouse, $movements];
    }
}
