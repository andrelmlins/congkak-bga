<?php

namespace Bga\Games\congkak\Managers;

use Bga\Games\congkak\Game;

class StatsManager
{
    private Game $game;

    function __construct($game)
    {
        $this->game = $game;
    }

    public function setup($players)
    {
        $this->game->bga->playerStats->init([
            'seedsStorehouse',
            'sowings',
            'seedsMyHouse',
            'sowingsOpponentHouse',
            'seedsGameEnd',
            'roundsWon',
            'averageSeedsRound',
        ], 0);
    }

    public function setSeedsStorehouse()
    {
        $houses = $this->game->houseManager->list();

        foreach ($houses as $playerId => $house) {
            $this->game->bga->playerStats->set('seedsStorehouse', $house['rumah'], (int) $playerId);
            $this->game->bga->playerStats->inc('averageSeedsRound', $house['rumah'], (int) $playerId);
        }
    }

    public function incSowings($playerId)
    {
        $this->game->bga->playerStats->inc('sowings', 1, (int) $playerId);
    }

    public function incSowingsOpponentHouse($playerId)
    {
        $this->game->bga->playerStats->inc('sowingsOpponentHouse', 1, (int) $playerId);
    }

    public function incSeedsMyHouse(int $count, $playerId)
    {
        $this->game->bga->playerStats->inc('seedsMyHouse', $count, (int) $playerId);
    }

    public function setSeedsGameEnd(int $count, $playerId)
    {
        $this->game->bga->playerStats->set('seedsGameEnd', $count, (int) $playerId);
    }

    public function incRoundsWon($playerId)
    {
        $this->game->bga->playerStats->inc('roundsWon', 1, (int) $playerId);
    }

    public function setAverageSeedsRound()
    {
        $players = $this->game->loadPlayersBasicInfos();
        $roundSettings = $this->game->optionsManager->getRoundSettings();

        foreach ($players as $player) {
            $playerId = (int) $player['player_id'];

            $value = $this->game->bga->playerStats->get('averageSeedsRound', $playerId);

            $this->game->bga->playerStats->set('averageSeedsRound', $value / $roundSettings['current'], $playerId);
        }
    }
}
