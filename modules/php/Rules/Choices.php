<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * The houses a player may choose to sow from (docs/RULES.md, sections 5 and 6). Each choice is `['location' => ..., 'playerId' => ..., 'isNext' => bool]`,
 * where `isNext` means the player is forced to go on from that house.
 */
final class Choices
{
    /**
     * Both players choosing at the same time: a player that must go on from a house has only that one, and a free player may take any of their own
     * kampongs with seeds, except the one the opponent is going on from.
     *
     * @param ?string $nextLocation where the player must go on from (`initial` or null: free choice)
     * @param mixed $nextPlayerId the owner of that house
     * @param ?string $opponentNextLocation where the opponent must go on from (`initial` or null: free)
     * @param mixed $opponentNextPlayerId the owner of that house
     * @param array $houses the board
     * @param array $locked `[playerId => [location => bool]]`
     */
    public static function simultaneous(
        $playerId,
        array $houses,
        array $locked,
        ?string $nextLocation,
        $nextPlayerId,
        ?string $opponentNextLocation,
        $opponentNextPlayerId
    ): array {
        if ($nextLocation !== null && $nextLocation !== 'initial') {
            return [['location' => $nextLocation, 'playerId' => $nextPlayerId, 'isNext' => true]];
        }

        $opponentNextLocation = $opponentNextLocation ?? 'initial';

        $result = [];

        for ($i = 1; $i <= Board::KAMPONGS; $i++) {
            $location = "kampong_{$i}";

            if ($houses[$playerId]['kampong'][$i] == 0) {
                continue;
            }
            if ($opponentNextPlayerId == $playerId && $opponentNextLocation == $location) {
                continue;
            }
            if (Board::isLocked($locked, $playerId, $location)) {
                continue;
            }

            $result[] = ['location' => $location, 'playerId' => $playerId, 'isNext' => false];
        }

        return $result;
    }

    /**
     * A single player in turn: forced to go on from a house, or free to take any of their own kampongs with seeds.
     */
    public static function single($playerId, array $houses, ?string $nextLocation, $nextPlayerId): array
    {
        if ($nextLocation !== null && $nextLocation !== 'initial') {
            return [['location' => $nextLocation, 'playerId' => $nextPlayerId, 'isNext' => true]];
        }

        $result = [];

        for ($i = 1; $i <= Board::KAMPONGS; $i++) {
            if ($houses[$playerId]['kampong'][$i] == 0) {
                continue;
            }

            $result[] = ['location' => "kampong_{$i}", 'playerId' => $playerId, 'isNext' => false];
        }

        return $result;
    }
}
