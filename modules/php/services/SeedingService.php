<?php

namespace Bga\Games\congkak\services;

use Bga\GameFramework\UserException;
use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\Game;
use Bga\Games\congkak\Rules\Board;
use Bga\Games\congkak\Rules\Choices;
use Bga\Games\congkak\Rules\SowingEnding;
use Bga\Games\congkak\Rules\SowingTurn;

/**
 * The sowing turns (docs/RULES.md, sections 5 and 6): the rules are in modules/php/Rules/, this class reads the board, saves the result, sends the
 * notifications and answers the states.
 */
class SeedingService
{
    public Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    /**
     * Sows the seeds of one or both players and settles how each sowing ends, then saves the board.
     *
     * @param array $picks per player, `['location' => 'kampong_N', 'playerId' => owner of the house]`
     * @return array see SowingTurn::resolve
     */
    public function resolve(array $picks): array
    {
        $houses = $this->game->houseService->list();
        $locked = $this->game->houseService->listLockeds();

        $result = SowingTurn::resolve($houses, $locked, $this->game->playerService->opponentMap(), $picks);

        $this->game->houseService->save($houses, $result['houses']);

        return $result;
    }

    /** Sends the notification (and counts the statistic) of how one player's sowing ended. */
    public function announceEnding($playerId, array $ending)
    {
        $playerName = $this->game->getPlayerNameById($playerId);

        switch ($ending['type']) {
            case SowingEnding::RUMAH:
                $this->game->bga->notify->all('endSeedingRumah', Messages::$EndSeedingRumah, [
                    'player_name' => $playerName
                ]);
                break;

            case SowingEnding::OCCUPIED:
                $this->game->bga->notify->all('endSeedingWithSeeds', Messages::$EndSeedingWithSeeds, [
                    'player_name' => $playerName
                ]);
                break;

            case SowingEnding::CAPTURE:
                $capture = $ending['capture'];

                $this->game->statsService->incSeedsMyHouse($capture['total'], $playerId);

                $this->game->bga->notify->all('moveAllToRumah', Messages::$EndSeeding, [
                    'player_name' => $playerName,
                    'location' => $capture['location'],
                    'playerId' => $playerId,
                    'opponentLocation' => $capture['opponentLocation'],
                    'opponentPlayerId' => $capture['opponentPlayerId']
                ]);
                $this->game->bga->notify->all('simplePause', '', ['time' => 1000]);
                break;

            case SowingEnding::OPPONENT:
                $this->game->statsService->incSowingsOpponentHouse($playerId);

                $this->game->bga->notify->all('endSeedingInOpponent', Messages::$EndSeedingInOpponent, [
                    'player_name' => $playerName
                ]);
                break;
        }
    }

    /** What a player may choose when both choose at the same time. */
    public function availableLocations($playerId)
    {
        $globals = $this->game->globals;
        $opponentId = $this->game->playerService->getOpponnetId($playerId);

        $nextHouseKey = "seeding:next:house:{$playerId}";

        return Choices::simultaneous(
            $playerId,
            $this->game->houseService->list(),
            $this->game->houseService->listLockeds(),
            $globals->has($nextHouseKey) ? $globals->get($nextHouseKey) : null,
            $globals->get("seeding:next:player:{$playerId}"),
            $globals->get("seeding:next:house:{$opponentId}"),
            $globals->get("seeding:next:player:{$opponentId}")
        );
    }

    public function argPlayerSeeding()
    {
        $globals = $this->game->globals;
        $activePlayerId = $this->game->getActivePlayerId();

        $locations = Choices::single(
            $activePlayerId,
            $this->game->houseService->list(),
            $globals->has('seeding:next:house') ? $globals->get('seeding:next:house') : null,
            $globals->get('seeding:next:player')
        );

        return ['locations' => $locations];
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
            throw new UserException(Messages::$InvalidAction);
        }

        $result = $this->resolve([$activePlayerId => ['location' => "kampong_{$house}", 'playerId' => $playerId]]);

        $this->game->bga->notify->all('playersSeeding', Messages::$PlayerSeeding, [
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'movements' => $result['movements'],
            'initialHouse' => $result['initialHouse'],
            'maxSeeds' => $result['maxSeeds'],
        ]);
        $this->game->bga->notify->all('simplePause', '', ['time' => 1000 + ($result['maxSeeds'] * 300)]);

        $ending = $result['endings'][$activePlayerId];

        $this->announceEnding($activePlayerId, $ending);

        $this->game->statsService->incSowings($activePlayerId);

        $this->game->bga->globals->delete("seeding:next:house");
        $this->game->bga->globals->delete("seeding:next:player");

        if ($ending['active']) {
            if ($this->game->houseService->isNewRound()) {
                return $this->game->gamestate->nextState("newRound");
            }

            $this->game->bga->globals->set("seeding:next:house", $ending['next']['location']);
            $this->game->bga->globals->set("seeding:next:player", $ending['next']['playerId']);

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
            throw new UserException(Messages::$InvalidAction);
        }

        $this->game->bga->globals->set("seeding:house:{$activePlayerId}", $house);
        $this->game->bga->globals->set("seeding:player:{$activePlayerId}", $playerId);

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
                $houseNumber = Board::houseNumber($locations[0]['location']);

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
                $houseNumber = Board::houseNumber($locations[0]['location']);

                $this->actPlayerSeeding($playerId, $houseNumber);
            }
        }
    }
}
