<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * The way the seeds travel (docs/RULES.md, section 4): clockwise, through the player's own kampongs from 7 to 1, the player's own rumah, the
 * opponent's kampongs from 7 to 1, and back. A player never drops a seed in the opponent's rumah, and burnt kampongs are skipped.
 */
final class SowingPath
{
    /**
     * @return array<int, array{playerId: string, location: string}> the full circuit of a player, rumah of the opponent included (it is skipped by `next`)
     */
    public static function sequence($playerId, $opponentId): array
    {
        $result = [];

        for ($i = Board::KAMPONGS; $i > 0; $i--) {
            $result[] = ['playerId' => strval($playerId), 'location' => "kampong_{$i}"];
        }
        $result[] = ['playerId' => strval($playerId), 'location' => Board::RUMAH];

        for ($i = Board::KAMPONGS; $i > 0; $i--) {
            $result[] = ['playerId' => $opponentId, 'location' => "kampong_{$i}"];
        }
        $result[] = ['playerId' => $opponentId, 'location' => Board::RUMAH];

        return $result;
    }

    /**
     * @param mixed $currentPlayerId the player that is sowing
     * @param mixed $playerId the owner of the house the last seed is in
     * @param string $house the location of that house
     * @param array $locked `[playerId => [location => bool]]`
     * @return array{playerId: string, location: string} the next house a seed of $currentPlayerId goes to
     */
    public static function next($currentPlayerId, $opponentId, $playerId, string $house, array $locked): array
    {
        $sequence = self::sequence($currentPlayerId, $opponentId);
        $found = array_search(['playerId' => $playerId, 'location' => $house], $sequence);
        $position = ($found === false ? 0 : $found) + 1;

        for ($guard = 0; $guard <= count($sequence) * 2; $guard++) {
            if ($position >= count($sequence)) {
                $position = 0;
            }

            $item = $sequence[$position];

            $isOpponentRumah = $item['location'] === Board::RUMAH && $item['playerId'] != $currentPlayerId;

            if ($isOpponentRumah || Board::isLocked($locked, $item['playerId'], $item['location'])) {
                $position++;
                continue;
            }

            return $item;
        }

        throw new \LogicException('Every house of the circuit is burnt.');
    }
}
