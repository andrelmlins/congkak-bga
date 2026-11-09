<?php

namespace Bga\Games\congkak\Managers;

use Bga\Games\congkak\entities\GameMode;
use Bga\Games\congkak\entities\VictoryMode;
use Bga\Games\congkak\Game;

/**
 * The table options (gameoptions.json) and the player preference (gamepreferences.json). Each default is the one of the option file.
 */
class OptionsManager
{
    private const GAME_MODE = 100;
    private const NUMBER_OF_ROUNDS = 101;
    private const HOUSE_BURNT = 102;
    private const VICTORY_MODE = 103;
    private const AUTOMATIC_SEEDING = 110;

    private const PREFERENCE_AUTOMATIC_SEEDING = 100;

    public Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    private function option(int $id, int $default): int
    {
        return $this->game->bga->tableOptions->get($id) ?? $default;
    }

    public function getRoundSettings()
    {
        $total = 1;

        if ($this->option(self::GAME_MODE, 1) == 2) {
            $total = $this->option(self::NUMBER_OF_ROUNDS, 3);
        } else if ($this->option(self::GAME_MODE, 1) == 3) {
            $total = null;
        }

        return [
            'current' => $this->game->bga->globals->get('round', 1),
            'total' => $total,
        ];
    }

    public function isBurnt()
    {
        return $this->option(self::GAME_MODE, 1) != 1 && $this->option(self::HOUSE_BURNT, 2) == 2;
    }

    public function isAutomaticSeeding($playerId)
    {
        return $this->option(self::AUTOMATIC_SEEDING, 2) == 2
            || $this->game->bga->userPreferences->get(intval($playerId), self::PREFERENCE_AUTOMATIC_SEEDING) == 1;
    }

    public function getVictoryMode()
    {
        $victoryMode = $this->option(self::VICTORY_MODE, 1);

        if ($victoryMode == 1) {
            return VictoryMode::NumberOfRounds;
        } else if ($victoryMode == 3) {
            return VictoryMode::NumberHousesBurned;
        }

        if ($this->getGameMode() == GameMode::TotalBurn) {
            return VictoryMode::NumberHousesBurned;
        }

        return VictoryMode::NumberOfSeeds;
    }

    public function getGameMode()
    {
        $gameMode = $this->option(self::GAME_MODE, 1);

        if ($gameMode == 1) {
            return GameMode::SingleRound;
        } else if ($gameMode == 2) {
            return GameMode::FixedRounds;
        }

        return GameMode::TotalBurn;
    }
}
