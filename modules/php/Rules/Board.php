<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * The seeds on the board, as plain arrays (docs/RULES.md, section 4):
 * `[playerId => ['kampong' => [1 => seeds, ..., 7 => seeds], 'rumah' => seeds]]`.
 * A location is `'rumah'` or `'kampong_N'` (N from 1 to 7). Pure, so it can be tested away from the framework (tests/).
 */
final class Board
{
    public const RUMAH = 'rumah';
    public const KAMPONGS = 7;
    public const SEEDS_PER_KAMPONG = 7;

    /** @param array<int|string> $playerIds @return array the board at the start of the game: 7 seeds in every kampong, empty rumah */
    public static function initial(array $playerIds): array
    {
        $houses = [];
        foreach ($playerIds as $playerId) {
            $houses[$playerId] = ['kampong' => array_fill(1, self::KAMPONGS, self::SEEDS_PER_KAMPONG), 'rumah' => 0];
        }

        return $houses;
    }

    /** @return int the number of a kampong location (`kampong_3` is 3) */
    public static function houseNumber(string $location): int
    {
        return intval(str_replace('kampong_', '', $location));
    }

    /** @return int the number of the kampong that faces kampong $number on the other side: the 1 faces the 7, the 2 the 6 (docs/RULES.md, section 4) */
    public static function opposite(int $number): int
    {
        return (self::KAMPONGS + 1) - $number;
    }

    public static function seeds(array $houses, $playerId, string $location): int
    {
        if ($location === self::RUMAH) {
            return $houses[$playerId]['rumah'];
        }

        return $houses[$playerId]['kampong'][self::houseNumber($location)];
    }

    public static function set(array &$houses, $playerId, string $location, int $seeds): void
    {
        if ($location === self::RUMAH) {
            $houses[$playerId]['rumah'] = $seeds;
            return;
        }

        $houses[$playerId]['kampong'][self::houseNumber($location)] = $seeds;
    }

    public static function add(array &$houses, $playerId, string $location, int $delta = 1): void
    {
        self::set($houses, $playerId, $location, self::seeds($houses, $playerId, $location) + $delta);
    }

    /** @return bool whether the location is burnt, in a `[playerId => [location => bool]]` map where a missing entry means not burnt */
    public static function isLocked(array $locked, $playerId, string $location): bool
    {
        return !empty($locked[$playerId][$location]);
    }
}
