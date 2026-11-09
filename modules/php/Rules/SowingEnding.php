<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * How a sowing ends, by where the last seed fell (docs/RULES.md, section 6).
 */
final class SowingEnding
{
    /** In the player's own rumah: plays again and chooses any house of their own. */
    public const RUMAH = 'rumah';
    /** In a house that already had seeds (either side): picks all of them up and sows again, with no choice. */
    public const OCCUPIED = 'occupied';
    /** In an empty house of their own: captures the seed and the opposite house into the rumah. The turn ends. */
    public const CAPTURE = 'capture';
    /** In an empty house of the opponent: nothing is captured. The turn ends. */
    public const OPPONENT = 'opponent';

    /**
     * @param array{location: string, playerId: mixed} $house where the last seed fell (the house is already counted in $houses)
     * @param array $houses the board after sowing
     */
    public static function classify(array $house, $playerId, array $houses): string
    {
        if ($house['location'] === Board::RUMAH) {
            return self::RUMAH;
        }

        if (Board::seeds($houses, $house['playerId'], $house['location']) > 1) {
            return self::OCCUPIED;
        }

        return $house['playerId'] == $playerId ? self::CAPTURE : self::OPPONENT;
    }

    /** @return bool whether the player goes on after this ending in the same turn */
    public static function isActive(string $type): bool
    {
        return $type === self::RUMAH || $type === self::OCCUPIED;
    }

    /**
     * @return array{location: string, playerId: mixed} where the player has to go on from: `initial` (free choice) after a rumah, the house after an
     *                                                  occupied one, and empty values when the turn ends
     */
    public static function next(string $type, array $house): array
    {
        if ($type === self::RUMAH) {
            return ['location' => 'initial', 'playerId' => ''];
        }

        if ($type === self::OCCUPIED) {
            return ['location' => $house['location'], 'playerId' => $house['playerId']];
        }

        return ['location' => '', 'playerId' => ''];
    }

    /**
     * @return array{0: array, 1: int, 2: string} the board after the capture, the seeds that went to the rumah, and the location of the opposite house
     */
    public static function capture(array $houses, $playerId, $opponentId, string $location): array
    {
        $opposite = 'kampong_' . Board::opposite(Board::houseNumber($location));

        $total = Board::seeds($houses, $playerId, $location) + Board::seeds($houses, $opponentId, $opposite);

        Board::add($houses, $playerId, Board::RUMAH, $total);
        Board::set($houses, $playerId, $location, 0);
        Board::set($houses, $opponentId, $opposite, 0);

        return [$houses, $total, $opposite];
    }
}
