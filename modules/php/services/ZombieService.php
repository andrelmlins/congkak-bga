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

    public function playerSeeding()
    {
        $locations = $this->game->argPlayerSeeding()['locations'];

        $playerId = $locations[0]['playerId'];
        $houseNumber = intval(str_replace('kampong_', '', $locations[0]['location']));

        $this->game->seedingService->actPlayerSeeding($playerId, $houseNumber);
    }

    public function playersSeeding($activePlayerId)
    {
        $locations = $this->game->seedingService->argPlayersSeeding()['locations'][$activePlayerId];

        $playerId = $locations[0]['playerId'];
        $houseNumber = intval(str_replace('kampong_', '', $locations[0]['location']));

        $this->game->seedingService->actPlayersSeeding($playerId, $houseNumber, $activePlayerId);
    }
}
