<?php

namespace Bga\Games\congkak\services;

use Bga\GameFramework\UserException;
use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\Game;
use Bga\Games\congkak\Rules\Board;
use Bga\Games\congkak\Rules\Choices;
use Bga\Games\congkak\Rules\SowingEnding;
use Bga\Games\congkak\Rules\SowingTurn;
use Bga\Games\congkak\States\NewRound;
use Bga\Games\congkak\States\NextPlayer;
use Bga\Games\congkak\States\PlayerSeeding;

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
        $globals = $this->game->bga->globals;
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
        $globals = $this->game->bga->globals;
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

    /**
     * The active player sows a house (docs/RULES.md, section 6): a free choice, or the house they were forced to go on from.
     *
     * @param int $playerId the owner of the house
     * @return string the next state: the same player goes on, the next player, or a new round
     */
    public function actPlayerSeeding(int $playerId, int $house, int $activePlayerId): string
    {
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

        if (!$ending['active']) {
            return NextPlayer::class;
        }

        if ($this->game->houseService->isNewRound()) {
            return NewRound::class;
        }

        $this->game->bga->globals->set("seeding:next:house", $ending['next']['location']);
        $this->game->bga->globals->set("seeding:next:player", $ending['next']['playerId']);

        $this->game->giveExtraTime($activePlayerId);

        return PlayerSeeding::class;
    }

    /**
     * One of the two players chooses, at the same time as the other, the house to sow. Only the choice is kept: the sowing happens when both chose
     * (see StepService::nextMultiplayers).
     *
     * @param int $playerId the player that chooses
     * @param int $ownerId the owner of the house
     */
    public function recordPick(int $playerId, int $ownerId, int $house): void
    {
        $houses = $this->game->houseService->list();

        if ($houses[$ownerId]['kampong'][$house] == 0) {
            throw new UserException(Messages::$InvalidAction);
        }

        $this->game->bga->globals->set("seeding:house:{$playerId}", $house);
        $this->game->bga->globals->set("seeding:player:{$playerId}", $ownerId);
    }

    /**
     * When the choice of a player is forced (they must go on from a house) and they asked for automatic seeding, records it for them.
     *
     * @return int[] the players that still have to choose
     */
    public function recordAutomaticPicks(): array
    {
        $locations = $this->argPlayersSeeding()['locations'];
        $manual = [];

        foreach ($this->game->loadPlayersBasicInfos() as $player) {
            $playerId = (int) $player['player_id'];
            $choices = $locations[$playerId];

            if ($this->game->optionsService->isAutomaticSeeding($playerId) && count($choices) == 1 && $choices[0]['isNext']) {
                $this->recordPick($playerId, (int) $choices[0]['playerId'], Board::houseNumber($choices[0]['location']));
            } else {
                $manual[] = $playerId;
            }
        }

        return $manual;
    }

    /**
     * @return ?array{playerId: int, house: int} the forced house of the active player when they asked for automatic seeding, null when they choose
     */
    public function automaticPick(int $activePlayerId): ?array
    {
        if (!$this->game->optionsService->isAutomaticSeeding($activePlayerId)) {
            return null;
        }

        $locations = $this->argPlayerSeeding()['locations'];

        if (count($locations) != 1 || !$locations[0]['isNext']) {
            return null;
        }

        return ['playerId' => (int) $locations[0]['playerId'], 'house' => Board::houseNumber($locations[0]['location'])];
    }
}
