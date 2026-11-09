<?php

declare(strict_types=1);

namespace Bga\Games\congkak\Rules;

/**
 * Who goes on after both players sowed at the same time (docs/RULES.md, section 5).
 */
final class Opening
{
    /** Only one player goes on, alone. */
    public const SINGLE = 'single';
    /** Nobody goes on: the first player of the table order plays. */
    public const FIRST_PLAYER = 'firstPlayer';
    /** Both are forced to go on from the same house, so they cannot sow together: the first player of the table order plays. */
    public const SAME_HOLE = 'sameHole';
    /** Both go on at the same time. */
    public const BOTH = 'both';

    /**
     * @param array $activePlayerIds the players that go on after their sowing
     * @param bool $sameChoices whether both players have exactly the same choices left (`Choices::simultaneous`)
     */
    public static function next(array $activePlayerIds, bool $sameChoices): string
    {
        if (count($activePlayerIds) === 1) {
            return self::SINGLE;
        }

        if (count($activePlayerIds) === 0) {
            return self::FIRST_PLAYER;
        }

        return $sameChoices ? self::SAME_HOLE : self::BOTH;
    }
}
