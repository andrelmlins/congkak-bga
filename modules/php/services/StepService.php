<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\Game;
use Bga\Games\congkak\Rules\Opening;
use Bga\Games\congkak\Rules\Reseed;

class StepService
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

        $picks = [];

        foreach ($players as $player) {
            $playerId = $player['player_id'];

            $house = $this->game->globals->get("seeding:house:{$playerId}");

            $picks[$playerId] = [
                'location' => "kampong_{$house}",
                'playerId' => $this->game->globals->get("seeding:player:{$playerId}"),
            ];
        }

        $result = $this->game->seedingService->resolve($picks);

        $this->game->notifyAllPlayers('playersSeeding', Messages::$PlayersSeeding, [
            'movements' => $result['movements'],
            'initialHouse' => $result['initialHouse'],
            'maxSeeds' => $result['maxSeeds'],
        ]);
        $this->game->notifyAllPlayers('simplePause', '', ['time' => 1000 + ($result['maxSeeds'] * 300)]);

        $actives = [];

        foreach ($result['endings'] as $playerId => $ending) {
            $this->game->giveExtraTime($playerId);
            $this->game->globals->delete("seeding:next:house:{$playerId}");
            $this->game->globals->delete("seeding:next:player:{$playerId}");
            $this->game->globals->delete("seeding:house:{$playerId}");
            $this->game->globals->delete("seeding:player:{$playerId}");

            $this->game->seedingService->announceEnding($playerId, $ending);

            if ($ending['active']) {
                $actives[] = $playerId;
            }

            $this->game->globals->set("seeding:next:house:{$playerId}", $ending['next']['location']);
            $this->game->globals->set("seeding:next:player:{$playerId}", $ending['next']['playerId']);

            $this->game->statsService->incSowings($playerId);
        }

        $sameChoices = false;

        if (count($actives) == 2) {
            $lastPlayerId = array_key_last($result['endings']);

            $sameChoices = $this->game->seedingService->availableLocations($lastPlayerId) ==
                $this->game->seedingService->availableLocations($this->game->playerService->getOpponnetId($lastPlayerId));
        }

        $next = Opening::next($actives, $sameChoices);

        if ($next == Opening::SINGLE) {
            $this->game->gamestate->changeActivePlayer($actives[0]);
            $this->game->gamestate->nextState('playerSeeding');
        } else if ($next == Opening::BOTH) {
            $this->game->gamestate->nextState('playersSeeding');
        } else {
            $first = $this->game->getObjectFromDB("SELECT player_id id FROM player WHERE player_no = 1");
            $this->game->gamestate->changeActivePlayer($first['id']);

            if ($next == Opening::SAME_HOLE) {
                $this->game->notifyAllPlayers('endSeedingSamePosition', Messages::$EndSeedingSamePosition, []);
            }

            $this->game->gamestate->nextState('playerSeeding');
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
        $burnt = $this->game->houseService->listLockeds();

        foreach ($list as $playerId => $houses) {
            $reseed = Reseed::compute($houses['rumah'], $burnt[$playerId] ?? []);

            $lockeds = $reseed['lockeds'];
            $movements = $reseed['movements'];
            $outwardMovementsCount = $reseed['outward'];

            foreach ($movements as $location => $count) {
                $this->game->houseService->update($playerId, $location, $count);
            }

            $this->game->houseService->update($playerId, 'rumah', $reseed['rumah']);

            $this->game->notifyAllPlayers('moveStorehouseSeeds', Messages::$MoveStorehouseSeeds, [
                'player_name' => $this->game->getPlayerNameById($playerId),
                'playerId' => $playerId,
                'movements' => $movements,
                'outwardMovementsCount' => $outwardMovementsCount,
            ]);
            $this->game->notifyAllPlayers('simplePause', '', ['time' => (count($movements) + 1) * 800]);

            if (count($lockeds) > 0 && $this->game->optionsService->isBurnt()) {
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
