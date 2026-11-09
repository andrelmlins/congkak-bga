<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\Game;
use Bga\Games\congkak\Rules\Opening;
use Bga\Games\congkak\Rules\Reseed;
use Bga\Games\congkak\States\NewRound;
use Bga\Games\congkak\States\PlayerSeeding;
use Bga\Games\congkak\States\PlayersSeeding;

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

                $this->game->bga->notify->all('moveRemainingSeeds', Messages::$MoveRemainingSeeds, [
                    'player_name' => $this->game->getPlayerNameById($playerId),
                    'playerId' => $playerId,
                ]);
                $this->game->bga->notify->all('simplePause', '', ['time' => 1000]);
            }
        }
    }

    /** After both players sowed at the same time (docs/RULES.md, section 5): settles the endings and says who goes on. @return string the next state */
    public function nextMultiplayers(): string
    {
        $players = $this->game->loadPlayersBasicInfos();

        $picks = [];

        foreach ($players as $player) {
            $playerId = $player['player_id'];

            $house = $this->game->bga->globals->get("seeding:house:{$playerId}");

            $picks[$playerId] = [
                'location' => "kampong_{$house}",
                'playerId' => $this->game->bga->globals->get("seeding:player:{$playerId}"),
            ];
        }

        $result = $this->game->seedingService->resolve($picks);

        $this->game->bga->notify->all('playersSeeding', Messages::$PlayersSeeding, [
            'movements' => $result['movements'],
            'initialHouse' => $result['initialHouse'],
            'maxSeeds' => $result['maxSeeds'],
        ]);
        $this->game->bga->notify->all('simplePause', '', ['time' => 1000 + ($result['maxSeeds'] * 300)]);

        $actives = [];

        foreach ($result['endings'] as $playerId => $ending) {
            $this->game->giveExtraTime($playerId);
            $this->game->bga->globals->delete("seeding:next:house:{$playerId}");
            $this->game->bga->globals->delete("seeding:next:player:{$playerId}");
            $this->game->bga->globals->delete("seeding:house:{$playerId}");
            $this->game->bga->globals->delete("seeding:player:{$playerId}");

            $this->game->seedingService->announceEnding($playerId, $ending);

            if ($ending['active']) {
                $actives[] = $playerId;
            }

            $this->game->bga->globals->set("seeding:next:house:{$playerId}", $ending['next']['location']);
            $this->game->bga->globals->set("seeding:next:player:{$playerId}", $ending['next']['playerId']);

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
            $this->game->gamestate->changeActivePlayer((int) $actives[0]);

            return PlayerSeeding::class;
        }

        if ($next == Opening::BOTH) {
            return PlayersSeeding::class;
        }

        $first = $this->game->getObjectFromDB("SELECT player_id id FROM player WHERE player_no = 1");
        $this->game->gamestate->changeActivePlayer((int) $first['id']);

        if ($next == Opening::SAME_HOLE) {
            $this->game->bga->notify->all('endSeedingSamePosition', Messages::$EndSeedingSamePosition, []);
        }

        return PlayerSeeding::class;
    }

    /** After a turn ends: a new round when a side is empty, else the other player. @return string the next state */
    public function nextPlayer(): string
    {
        $playerId = $this->game->getActivePlayerId();
        $this->game->giveExtraTime($playerId);

        if ($this->game->houseService->isNewRound()) {
            return NewRound::class;
        }

        $this->game->activeNextPlayer();

        return PlayerSeeding::class;
    }

    /** The first call starts the game, the next ones close a round and open the next (docs/RULES.md, section 8). @return string|int the next state, or the end of the game */
    public function newRound(): string|int
    {
        if (!$this->game->bga->globals->has('round')) {
            $this->game->bga->globals->set('round', 1);

            return PlayersSeeding::class;
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

            return Game::ST_END_GAME;
        }

        $this->game->bga->globals->inc('round', 1);
        $this->game->bga->notify->all('newRound', Messages::$NewRound, ['round' => $this->game->bga->globals->get('round')]);

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

            $this->game->bga->notify->all('moveStorehouseSeeds', Messages::$MoveStorehouseSeeds, [
                'player_name' => $this->game->getPlayerNameById($playerId),
                'playerId' => $playerId,
                'movements' => $movements,
                'outwardMovementsCount' => $outwardMovementsCount,
            ]);
            $this->game->bga->notify->all('simplePause', '', ['time' => (count($movements) + 1) * 800]);

            if (count($lockeds) > 0 && $this->game->optionsService->isBurnt()) {
                foreach ($lockeds as $location) {
                    $this->game->houseService->locked($playerId, $location);
                }

                $this->game->bga->notify->all('lockedHouses', Messages::$LockedHouses, [
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

            return Game::ST_END_GAME;
        }

        $this->game->gamestate->changeActivePlayer((int) $this->game->houseService->lowestSeededPlayer());

        return PlayerSeeding::class;
    }
}
