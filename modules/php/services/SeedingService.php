<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\Game;

class SeedingService extends \APP_GameClass
{
    public Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function moveAllToRumah($playerId, $houseLocation)
    {
        $houses = $this->game->houseService->list();
        $houseNumber = intval(str_replace('kampong_', '', $houseLocation));

        $opponentHouseNumber = 8 - $houseNumber;
        $opponentPlayerId = $this->game->playerService->getOpponnetId($playerId);

        $total = $houses[$playerId]['kampong'][$houseNumber] + $houses[$opponentPlayerId]['kampong'][$opponentHouseNumber];

        $this->game->houseService->updateInc($playerId, 'rumah', $total);
        $this->game->houseService->update($playerId, "kampong_{$houseNumber}", 0);
        $this->game->houseService->update($opponentPlayerId, "kampong_{$opponentHouseNumber}", 0);

        $this->game->statsService->incSeedsMyHouse($total, $playerId);

        $this->game->notifyAllPlayers('moveAllToRumah', Messages::$EndSeeding, [
            'player_name' => $this->game->getPlayerNameById($playerId),
            'location' => $houseLocation,
            'playerId' => $playerId,
            'opponentLocation' => "kampong_{$opponentHouseNumber}",
            'opponentPlayerId' => $opponentPlayerId
        ]);
        $this->game->notifyAllPlayers('simplePause', '', ['time' => 1000]);
    }

    public function seeding($maxSeeds, $initialHouse)
    {
        foreach ($initialHouse as $house) {
            $this->game->houseService->update($house['playerId'], $house['location'], 0);
        }

        $movements = [];
        $finalHouse = $initialHouse;

        for ($i = 0; $i < $maxSeeds; $i++) {
            foreach ($finalHouse as $playerId => $house) {
                if ($house['seeds'] == 0) continue;

                $nextHouse = $this->game->houseService->getNextHouse($playerId, $house['playerId'], $house['location']);

                if (!array_key_exists($playerId, $movements)) {
                    $movements[$playerId] = [];
                }

                $movements[$playerId][] = $nextHouse;

                $this->game->houseService->updateInc($nextHouse['playerId'], $nextHouse['location'], 1);

                $finalHouse[$playerId] = [
                    'location' => $nextHouse['location'],
                    'playerId' => $nextHouse['playerId'],
                    'seeds' => $house['seeds'] - 1
                ];
            }
        }

        return [$finalHouse, $movements];
    }

    public function finishSeeding($house, $playerId)
    {
        $newHouses = $this->game->houseService->list();

        $location = '';
        $locationPlayer = '';
        $active = false;

        if ($house['location'] == 'rumah') {
            $location = 'initial';
            $active = true;

            $this->game->notifyAllPlayers('endSeedingRumah', Messages::$EndSeedingRumah, [
                'player_name' => $this->game->getPlayerNameById($playerId)
            ]);
        } else {
            $houseNumber = intval(str_replace('kampong_', '', $house['location']));
            $seeds = $newHouses[$house['playerId']]['kampong'][$houseNumber];

            if ($seeds > 1) {
                $location = $house['location'];
                $locationPlayer = $house['playerId'];
                $active = true;

                $this->game->notifyAllPlayers('endSeedingWithSeeds', Messages::$EndSeedingWithSeeds, [
                    'player_name' => $this->game->getPlayerNameById($playerId)
                ]);
            } else if ($house['playerId'] == $playerId) {
                $this->moveAllToRumah($playerId, $house['location']);
            } else {
                $this->game->statsService->incSowingsOpponentHouse($playerId);

                $this->game->notifyAllPlayers('endSeedingInOpponent', Messages::$EndSeedingInOpponent, [
                    'player_name' => $this->game->getPlayerNameById($playerId)
                ]);
            }
        }

        return [
            ['location' => $location, 'playerId' => $locationPlayer],
            $active
        ];
    }

    public function availableLocations($playerId)
    {
        if ($this->game->globals->has("seeding:next:house:{$playerId}") && $this->game->globals->get("seeding:next:house:{$playerId}") != 'initial') {
            $location = $this->game->globals->get("seeding:next:house:{$playerId}");
            $locationPlayerId = $this->game->globals->get("seeding:next:player:{$playerId}");

            return [['location' => $location, 'playerId' => $locationPlayerId, 'isNext' => true]];
        }

        $opponentPlayerId = $this->game->playerService->getOpponnetId($playerId);
        $opponentLocation = $this->game->globals->get("seeding:next:house:{$opponentPlayerId}") ?? 'initial';
        $opponentLocationPlayerId = $this->game->globals->get("seeding:next:player:{$opponentPlayerId}");

        $result = [];
        $houses = $this->game->houseService->list()[$playerId];

        for ($i = 1; $i <= 7; $i++) {
            if ($houses['kampong'][$i] == 0) continue;
            if ($opponentLocationPlayerId == $playerId && $opponentLocation == "kampong_{$i}") continue;
            if ($this->game->houseService->isLocked($playerId, "kampong_{$i}")) continue;

            $result[] = ['location' => "kampong_{$i}", 'playerId' => $playerId, 'isNext' => false];
        }

        return $result;
    }

    public function argPlayerSeeding()
    {
        if ($this->game->globals->has("seeding:next:house") && $this->game->globals->get("seeding:next:house") != 'initial') {
            $location = $this->game->globals->get("seeding:next:house");
            $locationPlayerId = $this->game->globals->get("seeding:next:player");

            return ['locations' => [['location' => $location, 'playerId' => $locationPlayerId, 'isNext' => true]]];
        }

        $activePlayerId = $this->game->getActivePlayerId();
        $houses = $this->game->houseService->list()[$activePlayerId];
        $result = [];

        for ($i = 1; $i <= 7; $i++) {
            if ($houses['kampong'][$i] == 0) continue;

            $result[] = ['location' => "kampong_{$i}", 'playerId' => $activePlayerId, 'isNext' => false];
        }

        return ['locations' => $result];
    }

    public function argPlayersSeeding()
    {
        $result = [];
        $players = $this->game->loadPlayersBasicInfos();

        foreach ($players as $player) {
            $playerId = $player['player_id'];
            $result[$playerId] = $this->availableLocations($playerId);
        }

        return ['locations' => $result];
    }

    public function actPlayerSeeding(string $playerId, int $house)
    {
        $activePlayerId = $this->game->getActivePlayerId();

        $houses = $this->game->houseService->list();

        if ($houses[$playerId]['kampong'][$house] == 0) {
            throw new \BgaUserException(Messages::$InvalidAction, 9000);
        }

        $initialHouse = [
            $activePlayerId => [
                'location' => "kampong_{$house}",
                'playerId' => $playerId,
                'seeds' => $houses[$playerId]['kampong'][$house]
            ]
        ];

        $maxSeeds = $houses[$playerId]['kampong'][$house];

        list($housePerPlayer, $movements) = $this->seeding($maxSeeds, $initialHouse);

        $this->game->notifyAllPlayers('playersSeeding', Messages::$PlayerSeeding, [
            'player_name' => $this->game->getPlayerNameById($playerId),
            'movements' => $movements,
            'initialHouse' => $initialHouse,
            'maxSeeds' => $maxSeeds,
        ]);
        $this->game->notifyAllPlayers('simplePause', '', ['time' => 1000 + ($maxSeeds * 300)]);

        list($location, $active) = $this->finishSeeding($housePerPlayer[$activePlayerId], $activePlayerId);

        $this->game->statsService->incSowings($activePlayerId);

        $this->game->globals->delete("seeding:next:house");
        $this->game->globals->delete("seeding:next:player");

        if ($active) {
            if ($this->game->houseService->isNewRound()) {
                return $this->game->gamestate->nextState("newRound");
            }

            $this->game->globals->set("seeding:next:house", $location['location']);
            $this->game->globals->set("seeding:next:player", $location['playerId']);

            $this->game->giveExtraTime($activePlayerId);
            $this->game->gamestate->nextState('playerSeeding');
        } else {
            $this->game->gamestate->nextState('nextPlayer');
        }
    }

    public function actPlayersSeeding(string $playerId, int $house, ?string $localPlayerId)
    {
        $activePlayerId = $localPlayerId ?? $this->game->getLocalCurrentPlayerId();

        $houses = $this->game->houseService->list();

        if ($houses[$playerId]['kampong'][$house] == 0) {
            throw new \BgaUserException(Messages::$InvalidAction, 9000);
        }

        $this->game->globals->set("seeding:house:{$activePlayerId}", $house);
        $this->game->globals->set("seeding:player:{$activePlayerId}", $playerId);

        $this->game->gamestate->setPlayerNonMultiactive($activePlayerId, 'nextMultiplayers');
    }

    public function stPlayersSeeding()
    {
        $players = $this->game->loadPlayersBasicInfos();
        $playersLocations = $this->argPlayersSeeding()['locations'];
        $result = [];

        foreach ($players as $player) {
            $locations = $playersLocations[$player['player_id']];

            if (
                $this->game->optionsService->isAutomaticSeeding($player['player_id']) &&
                count($locations) == 1 &&
                $locations[0]['isNext']
            ) {
                $playerId = $locations[0]['playerId'];
                $houseNumber = intval(str_replace('kampong_', '', $locations[0]['location']));

                $this->actPlayersSeeding($playerId, $houseNumber, $player['player_id']);
            } else {
                $result[] = $player['player_id'];
            }
        }

        $this->game->gamestate->setPlayersMultiactive($result, 'nextMultiplayers');
    }

    public function stPlayerSeeding()
    {
        $activePlayerId = $this->game->getActivePlayerId();

        if ($this->game->optionsService->isAutomaticSeeding($activePlayerId)) {
            $locations = $this->argPlayerSeeding()['locations'];

            if (count($locations) == 1 && $locations[0]['isNext']) {
                $playerId = $locations[0]['playerId'];
                $houseNumber = intval(str_replace('kampong_', '', $locations[0]['location']));

                $this->actPlayerSeeding($playerId, $houseNumber);
            }
        }
    }
}
