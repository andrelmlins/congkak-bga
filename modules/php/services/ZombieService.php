<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\Game;

class ZombieService
{
    private Game $game;

    function __construct($game)
    {
        $this->game = $game;
    }

    public function run($state, $activePlayerId)
    {
        $stateName = $state['name'];

        switch ($stateName) {
            case 'playersSeeding':
                return $this->playersSeeding($activePlayerId);
            case 'playerSeeding':
                return $this->playerSeeding($activePlayerId);
        }
    }

    public function playerSeeding($activePlayerId)
    {
        $location = $this->game->argPlayerSeeding()['location'];

        if ($location['location'] != 'initial') {
            $playerId = $location['playerId'];
            $houseNumber = intval(str_replace('kampong_', '', $location['location']));

            $this->game->seedingService->actPlayerSeeding($playerId, $houseNumber);
        } else {
            $houses = $this->game->houseService->list();

            for ($i = 1; $i <= 7; $i++) {
                if ($houses[$activePlayerId]['kampong'][$i] > 0) {
                    $this->game->seedingService->actPlayerSeeding($activePlayerId, $i);

                    break;
                }
            }
        }
    }

    public function playersSeeding($activePlayerId)
    {
        $location = $this->game->seedingService->argPlayersSeeding()['locations'][$activePlayerId];

        if ($location['location'] != 'initial') {
            $playerId = $location['playerId'];
            $houseNumber = intval(str_replace('kampong_', '', $location['location']));

            $this->game->seedingService->actPlayersSeeding($playerId, $houseNumber, $activePlayerId);
        } else {
            $houses = $this->game->houseService->list();

            for ($i = 1; $i <= 7; $i++) {
                if ($houses[$activePlayerId]['kampong'][$i] > 0) {
                    $this->game->seedingService->actPlayersSeeding($activePlayerId, $i, $activePlayerId);

                    break;
                }
            }
        }
    }
}
