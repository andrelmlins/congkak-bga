<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * One sowing by one player, or the simultaneous sowing of both (docs/RULES.md, sections 5 and 6): sows the seeds, then settles how each ends, in the
 * order of the players, so the second one already sees what the first captured.
 */
final class SowingTurn
{
    /**
     * @param array $houses the board (see Board)
     * @param array $locked `[playerId => [location => bool]]`
     * @param array $opponentOf `[playerId => opponentId]`
     * @param array<int|string, array{location: string, playerId: mixed}> $picks per player, the house the seeds are taken from
     * @return array{houses: array, initialHouse: array, maxSeeds: int, movements: array, endings: array} the board after sowing and captures, what each
     *         player took (`location`, `playerId` of the owner, `seeds`), the most seeds of any of them, the houses each seed went to, and per player
     *         its ending: `type` (SowingEnding), `active`, `next` (SowingEnding::next), `house` (where the last seed fell) and, for a capture,
     *         `capture` (`total`, `location`, `opponentLocation`, `opponentPlayerId`)
     */
    public static function resolve(array $houses, array $locked, array $opponentOf, array $picks): array
    {
        $initialHouse = [];
        $maxSeeds = 0;

        foreach ($picks as $playerId => $pick) {
            $seeds = Board::seeds($houses, $pick['playerId'], $pick['location']);

            $initialHouse[$playerId] = ['location' => $pick['location'], 'playerId' => $pick['playerId'], 'seeds' => $seeds];

            if ($seeds > $maxSeeds) {
                $maxSeeds = $seeds;
            }
        }

        [$houses, $finalHouse, $movements] = Sowing::sow($houses, $locked, $opponentOf, $initialHouse, $maxSeeds);

        $endings = [];

        foreach ($finalHouse as $playerId => $house) {
            $type = SowingEnding::classify($house, $playerId, $houses);

            $ending = [
                'type' => $type,
                'active' => SowingEnding::isActive($type),
                'next' => SowingEnding::next($type, $house),
                'house' => ['location' => $house['location'], 'playerId' => $house['playerId']],
            ];

            if ($type === SowingEnding::CAPTURE) {
                [$houses, $total, $opposite] = SowingEnding::capture($houses, $playerId, $opponentOf[$playerId], $house['location']);

                $ending['capture'] = [
                    'total' => $total,
                    'location' => $house['location'],
                    'opponentLocation' => $opposite,
                    'opponentPlayerId' => $opponentOf[$playerId],
                ];
            }

            $endings[$playerId] = $ending;
        }

        return [
            'houses' => $houses,
            'initialHouse' => $initialHouse,
            'maxSeeds' => $maxSeeds,
            'movements' => $movements,
            'endings' => $endings,
        ];
    }
}
