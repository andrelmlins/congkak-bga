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

        list($housePerPlayer, $movements) = $this->game->seedingService->seeding($maxSeeds, $initialHouse);

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

            list($location, $active) = $this->game->seedingService->finishSeeding($house, $playerId);

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
        } else if (
            $this->game->seedingService->availableLocations($playerId) ==
            $this->game->seedingService->availableLocations($this->game->playerService->getOpponnetId($playerId))
        ) {
            $result = $this->game->getObjectFromDB("SELECT player_id id FROM player WHERE player_no = 1");
            $this->game->gamestate->changeActivePlayer($result['id']);

            $this->game->notifyAllPlayers('endSeedingSamePosition', Messages::$EndSeedingSamePosition, []);

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

        $this->moveRemainingSeeds();
        $this->game->statsService->setSeedsStorehouse();
        $this->game->playerService->setRoundWin();

        $players = $this->game->loadPlayersBasicInfos();
        foreach ($players as $player) {
            $this->game->playerService->updateScore($player['player_id']);
        }

        if ($this->game->houseService->isRoundGameEnd()) {
            $this->game->statsService->setAverageSeedsRound();
            return $this->game->gamestate->nextState('gameEnd');
        }

        $this->game->globals->inc('round', 1);
        $this->game->notifyAllPlayers('newRound', Messages::$NewRound, ['round' => $this->game->globals->get('round')]);

        $list = $this->game->houseService->list();

        foreach ($list as $playerId => $houses) {
            $total = $houses['rumah'];
            $lockeds = [];
            $movements = [];
            $outwardMovementsCount = 0;

            for ($i = 1; $i <= 7; $i++) {
                if ($this->game->houseService->isLocked($playerId, "kampong_{$i}")) {
                    $outwardMovementsCount = $total;
                    $total = 0;
                    break;
                }

                if ($total == 0) {
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
                'outwardMovementsCount' => $outwardMovementsCount,
            ]);
            $this->game->notifyAllPlayers('simplePause', '', ['time' => (count($movements) + 1) * 800]);

            if ($lockeds > 0 && $this->game->optionsService->isBurnt()) {
                foreach ($lockeds as $location) {
                    $this->game->houseService->locked($playerId, $location);
                }

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
            $this->game->statsService->setAverageSeedsRound();
            return $this->game->gamestate->nextState('gameEnd');
        }

        $this->game->gamestate->changeActivePlayer($this->game->houseService->lowestSeededPlayer());
        $this->game->gamestate->nextState('playerSeeding');
    }
}
