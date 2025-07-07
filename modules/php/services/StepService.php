<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\Game;

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

    public function moveRemainingSeeds()
    {
        $houses = $this->game->houseService->list();

        foreach ($houses as $playerId => $house) {
            $total = 0;

            foreach ($house['kampong'] as $number => $seeds) {
                $total += $seeds;

                $this->game->houseService->update($playerId, "kampong_{$number}", 0);
            }

            if ($total > 0) {
                $this->game->houseService->updateInc($playerId, 'rumah', $total);
                $this->game->statsService->setSeedsGameEnd($total, $playerId);

                $this->game->notifyAllPlayers('moveRemainingSeeds', Messages::$MoveRemainingSeeds, [
                    'player_name' => $this->game->getPlayerNameById($playerId),
                    'playerId' => $playerId,
                ]);
                $this->game->notifyAllPlayers('simplePause', '', ['time' => 1000]);
            }
        }
    }

    public function argPlayerSeeding()
    {
        return [
            'location' => [
                'location' => $this->game->globals->get("seeding:next:house") ?? 'initial',
                'playerId' => $this->game->globals->get("seeding:next:player")
            ]
        ];
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
                $this->moveRemainingSeeds();

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

        $initialHouse = [];

        foreach ($players as $player) {
            $playerId = $player['player_id'];

            $house = $this->game->globals->get("seeding:house:{$playerId}");
            $playerLocation = $this->game->globals->get("seeding:player:{$playerId}");

            $initialHouse[$playerId] = [
                'location' => "kampong_{$house}",
                'playerId' => $playerLocation,
                'seeds' => $houses[$playerLocation]['kampong'][$house]
            ];

            if ($houses[$playerLocation]['kampong'][$house] > $maxSeeds) {
                $maxSeeds = $houses[$playerLocation]['kampong'][$house];
            }
        }

        list($housePerPlayer, $movements) = $this->seeding($maxSeeds, $initialHouse);

        $this->game->notifyAllPlayers('playersSeeding', Messages::$PlayersSeeding, [
            'movements' => $movements,
            'initialHouse' => $initialHouse,
            'maxSeeds' => $maxSeeds,
        ]);
        $this->game->notifyAllPlayers('simplePause', '', ['time' => 1000 + ($maxSeeds * 300)]);

        $actives = [];

        foreach ($housePerPlayer as $playerId => $house) {
            $this->game->giveExtraTime($playerId);
            $this->game->globals->delete("seeding:next:house:{$playerId}");
            $this->game->globals->delete("seeding:next:player:{$playerId}");
            $this->game->globals->delete("seeding:house:{$playerId}");
            $this->game->globals->delete("seeding:player:{$playerId}");

            list($location, $active) = $this->finishSeeding($house, $playerId);

            if ($active) {
                $actives[] = $playerId;
            }

            $this->game->globals->set("seeding:next:house:{$playerId}", $location['location']);
            $this->game->globals->set("seeding:next:player:{$playerId}", $location['playerId']);

            $this->game->statsService->incSowings($playerId);
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

    public function stNextPlayer()
    {
        $playerId = $this->game->getActivePlayerId();
        $this->game->giveExtraTime($playerId);

        if ($this->game->houseService->isNewRound()) {
            $this->moveRemainingSeeds();

            return $this->game->gamestate->nextState("newRound");
        }

        $this->game->localActiveNextPlayer();

        $this->game->gamestate->nextState("playerSeeding");
    }

    public function stNewRound()
    {
        if (!$this->game->globals->has('round')) {
            $this->game->globals->set('round', 1);

            return $this->game->gamestate->nextState('playersSeeding');
        }

        $this->game->globals->inc('round', 1);
        $this->game->notifyAllPlayers('newRound', Messages::$NewRound, []);

        $list = $this->game->houseService->list();

        foreach ($list as $playerId => $houses) {
            $total = $houses['rumah'];
            $lockeds = [];
            $movements = [];

            for ($i = 1; $i <= 7; $i++) {
                if ($this->game->houseService->isLocked($playerId, "kampong_{$i}")) {
                    continue;
                }

                if ($total == 0) {
                    $this->game->houseService->locked($playerId, "kampong_{$i}");
                    $lockeds[] = "kampong_{$i}";
                    continue;
                }

                $count = 7;

                if ($total < 7) {
                    $count = $total;
                }

                $total -= $count;
                $movements["kampong_{$i}"] = $count;

                $this->game->houseService->update($playerId, "kampong_{$i}", $count);
            }

            $this->game->houseService->update($playerId, 'rumah', $total);

            $this->game->notifyAllPlayers('moveStorehouseSeeds', Messages::$MoveStorehouseSeeds, [
                'player_name' => $this->game->getPlayerNameById($playerId),
                'playerId' => $playerId,
                'movements' => $movements,
            ]);
            $this->game->notifyAllPlayers('simplePause', '', ['time' => (count($movements) + 1) * 800]);

            if ($lockeds > 0) {
                $this->game->notifyAllPlayers('lockedHouses', Messages::$LockedHouses, [
                    'player_name' => $this->game->getPlayerNameById($playerId),
                    'count' => count($lockeds),
                    'playerId' => $playerId,
                    'lockeds' => $lockeds
                ]);
            }

            $this->game->playerService->updateScore($playerId);
        }

        if ($this->game->houseService->isGameEnd()) {
            return $this->game->gamestate->nextState('gameEnd');
        }

        $this->game->gamestate->changeActivePlayer($this->game->houseService->lowestSeededPlayer());
        $this->game->gamestate->nextState('playerSeeding');
    }
}
