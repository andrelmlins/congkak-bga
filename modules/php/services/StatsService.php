<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\Game;

class StatsService
{
    private Game $game;

    function __construct($game)
    {
        $this->game = $game;
    }

    public function setup($players)
    {
        foreach ($players as $playerId => $player) {
            $this->game->initStat('player', 'seedsStorehouse', 0, $playerId);
            $this->game->initStat('player', 'sowings', 0, $playerId);
            $this->game->initStat('player', 'seedsMyHouse', 0, $playerId);
            $this->game->initStat('player', 'sowingsOpponentHouse', 0, $playerId);
            $this->game->initStat('player', 'seedsGameEnd', 0, $playerId);
            $this->game->initStat('player', 'roundsWon', 0, $playerId);
        }
    }

    public function setSeedsStorehouse()
    {
        $houses = $this->game->houseService->list();

        foreach ($houses as $playerId => $house) {
            $this->game->setStat($house['rumah'], 'seedsStorehouse', $playerId);
            $this->game->incStat($house['rumah'], 'averageSeedsRound', $playerId);
        }
    }

    public function incSowings($playerId)
    {
        $this->game->incStat(1, 'sowings', $playerId);
    }

    public function incSowingsOpponentHouse($playerId)
    {
        $this->game->incStat(1, 'sowingsOpponentHouse', $playerId);
    }

    public function incSeedsMyHouse(int $count, $playerId)
    {
        $this->game->incStat($count, 'seedsMyHouse', $playerId);
    }

    public function setSeedsGameEnd(int $count, $playerId)
    {
        $this->game->setStat($count, 'seedsGameEnd', $playerId);
    }

    public function incRoundsWon($playerId)
    {
        $this->game->incStat(1, 'roundsWon', $playerId);
    }

    public function setAverageSeedsRound()
    {
        $players = $this->game->loadPlayersBasicInfos();
        $roundSettings = $this->game->optionsService->getRoundSettings();

        foreach ($players as $player) {
            $value = $this->game->getStat('averageSeedsRound', $player['player_id']);

            $this->game->setStat($value / $roundSettings['current'], 'averageSeedsRound', $player['player_id']);
        }
    }
}
