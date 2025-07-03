<?php

namespace Bga\Games\congkak;

class StepService extends \APP_GameClass
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

        $sql = "UPDATE `house` SET house_seeds = '%s' WHERE house_location = '%s' AND house_player = %s";
        $this->game->DbQuery(sprintf($sql, $total, 'rumah', $playerId));

        $sql = "UPDATE `house` SET house_seeds = 0 WHERE house_location = '%s' AND house_player = %s";
        $this->game->DbQuery(sprintf($sql, "kampong_{$houseNumber}", $playerId));

        $sql = "UPDATE `house` SET house_seeds = 0 WHERE house_location = '%s' AND house_player != %s";
        $this->game->DbQuery(sprintf($sql, "kampong_{$opponentHouseNumber}", $playerId));
    }

    public function argPlayerSeeding()
    {
        return [];
    }

    public function argPlayersSeeding()
    {
        $result = [];
        $players = $this->game->loadPlayersBasicInfos();

        foreach ($players as $player) {
            $playerId = $player['player_id'];
            $result[$playerId] = [
                'location' => $this->game->globals->get("seeding:next:house:{$playerId}") ?? 'initial',
                'playerId' => $this->game->globals->get("seeding:next:player:{$playerId}")
            ];
        }

        return ['locations' => $result];
    }

    public function actPlayersSeeding(string $playerId, int $house)
    {
        $activePlayerId = $this->game->getLocalCurrentPlayerId();

        $houses = $this->game->houseService->list();

        if ($houses[$playerId]['kampong'][$house] == 0) {
            throw new \BgaUserException(Messages::$InvalidAction, 9000);
        }

        $this->game->globals->set("seeding:house:{$activePlayerId}", $house);
        $this->game->globals->set("seeding:player:{$activePlayerId}", $playerId);

        $this->game->gamestate->setPlayerNonMultiactive($activePlayerId, 'nextMultiplayers');
    }

    public function stNextMultiplayers()
    {
        $players = $this->game->loadPlayersBasicInfos();
        $houses = $this->game->houseService->list();

        $maxSeeds = 0;

        $housePerPlayer = [];
        $movements = [];
        $initialHouse = [];

        foreach ($players as $player) {
            $playerId = $player['player_id'];

            $house = $this->game->globals->get("seeding:house:{$playerId}");
            $playerLocation = $this->game->globals->get("seeding:player:{$playerId}");

            $housePerPlayer[$playerId] = [
                'location' => "kampong_{$house}",
                'playerId' => $playerLocation,
                'seeds' => $houses[$playerLocation]['kampong'][$house]
            ];
            $initialHouse[$playerId] = [
                'location' => "kampong_{$house}",
                'playerId' => $playerLocation,
            ];
            $movements[$playerId] = [];

            if ($houses[$playerLocation]['kampong'][$house] > $maxSeeds) {
                $maxSeeds = $houses[$playerLocation]['kampong'][$house];
            }
        }

        foreach ($housePerPlayer as $house) {
            $this->game->houseService->update($house['playerId'], $house['location'], 0);
        }

        for ($i = 0; $i < $maxSeeds; $i++) {
            foreach ($housePerPlayer as $playerId => $house) {
                if ($house['seeds'] == 0) continue;

                $nextHouse = $this->game->houseService->getNextHouse($playerId, $house['playerId'], $house['location']);

                $movements[$playerId][] = $nextHouse;

                $this->game->houseService->updateInc($nextHouse['playerId'], $nextHouse['location'], 1);

                $housePerPlayer[$playerId] = [
                    'location' => $nextHouse['location'],
                    'playerId' => $nextHouse['playerId'],
                    'seeds' => $house['seeds'] - 1
                ];
            }
        }

        $newHouses = $this->game->houseService->list();
        $actives = [];

        $this->game->notifyAllPlayers('playersSeeding', Messages::$PlayersSeeding, [
            'movements' => $movements,
            'initialHouse' => $initialHouse,
            'maxSeeds' => $maxSeeds,
        ]);
        $this->game->notifyAllPlayers('simplePause', '', ['time' => 500 + ($maxSeeds * 300)]);

        foreach ($housePerPlayer as $playerId => $house) {
            $this->game->giveExtraTime($playerId);
            $this->game->globals->delete("seeding:next:house:{$playerId}");
            $this->game->globals->delete("seeding:next:player:{$playerId}");
            $this->game->globals->delete("seeding:house:{$playerId}");
            $this->game->globals->delete("seeding:player:{$playerId}");

            if ($house['location'] == 'rumah') {
                $this->game->globals->set("seeding:next:house:{$playerId}", 'initial');
                $actives[] = $playerId;

                $this->game->notifyAllPlayers('endSeedingRumah', Messages::$EndSeedingRumah, [
                    'player_name' => $this->game->getPlayerNameById($playerId)
                ]);
            } else {
                $houseNumber = intval(str_replace('kampong_', '', $house['location']));
                $seeds = $newHouses[$house['playerId']]['kampong'][$houseNumber];

                if ($seeds > 1) {
                    $this->game->globals->set("seeding:next:house:{$playerId}", $house['location']);
                    $this->game->globals->set("seeding:next:player:{$playerId}", $house['playerId']);
                    $actives[] = $playerId;

                    $this->game->notifyAllPlayers('endSeedingWithSeeds', Messages::$EndSeedingWithSeeds, [
                        'player_name' => $this->game->getPlayerNameById($playerId)
                    ]);
                } else if ($house['playerId'] == $playerId) {
                    $this->game->notifyAllPlayers('endSeedingWithoutMyHouse', Messages::$EndSeeding, [
                        'player_name' => $this->game->getPlayerNameById($playerId),
                        'house' => $house,
                    ]);
                    $this->game->notifyAllPlayers('simplePause', '', ['time' => 1000]);

                    $this->moveAllToRumah($playerId, $house['location']);
                } else {
                    $this->game->notifyAllPlayers('endSeedingWithout', Messages::$EndSeedingInOpponent, [
                        'player_name' => $this->game->getPlayerNameById($playerId)
                    ]);
                }
            }
        }

        if (count($actives) == 1) {
            $this->game->gamestate->changeActivePlayer($actives[0]);
            $this->game->gamestate->nextState('playerSeeding');
        } else if (count($actives) == 0) {
            $result = $this->game->getObjectFromDB("SELECT player_id id FROM player WHERE player_no = 1");
            $this->game->gamestate->changeActivePlayer($result['id']);
            $this->game->gamestate->nextState('playerSeeding');
        } else {
            $this->game->gamestate->nextState('playersSeeding');
        }
    }
}
