<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\entities\GameMode;
use Bga\Games\congkak\entities\VictoryMode;
use Bga\Games\congkak\Game;

class OptionsService extends \APP_GameClass
{
    public Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function getRoundSettings()
    {
        $total = 1;

        if ($this->game->getGameStateValue('gameMode') == 2) {
            $total = $this->game->getGameStateValue('numberOfRounds');
        } else if ($this->game->getGameStateValue('gameMode') == 3) {
            $total = null;
        }

        return [
            'current' => $this->game->globals->get('round', 1),
            'total' => $total,
        ];
    }

    public function isBurnt()
    {
        return $this->game->getGameStateValue('gameMode') != 1 && $this->game->getGameStateValue('houseBurnt') == 2;
    }

    public function isAutomaticSeeding($playerId)
    {
        return $this->game->getGameStateValue('automaticSeeding') == 2 || $this->game->userPreferences->get(intval($playerId), 100) == 1;
    }

    public function getVictoryMode()
    {
        $victoryMode = $this->game->getGameStateValue('victoryMode');

        if ($victoryMode == 1) {
            return VictoryMode::NumberOfRounds;
        } else if ($victoryMode == 3) {
            return VictoryMode::NumberHousesBurned;
        }

        return VictoryMode::NumberOfSeeds;
    }

    public function getGameMode()
    {
        $gameMode = $this->game->getGameStateValue('gameMode');

        if ($gameMode == 1) {
            return GameMode::SingleRound;
        } else if ($gameMode == 2) {
            return GameMode::FixedRounds;
        }

        return GameMode::TotalBurn;
    }
}
