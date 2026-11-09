<?php

declare(strict_types=1);

namespace Bga\Games\congkak\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\congkak\Game;
use Bga\Games\congkak\Rules\Board;

/** Both players choose a house at the same time (docs/RULES.md, section 5): the sowing happens when both chose. */
class PlayersSeeding extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 10,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'playersSeeding',
            description: clienttranslate('Waiting for other players to perform their action'),
            descriptionMyTurn: clienttranslate('${you} must select a house to sow'),
        );
    }

    /** @return array{locations: array} per player, the houses they may choose */
    function getArgs(): array
    {
        return $this->game->seedingService->argPlayersSeeding();
    }

    /** Players with a forced house and automatic seeding on are recorded at once, the others are asked. */
    function onEnteringState()
    {
        $manual = $this->game->seedingService->recordAutomaticPicks();

        $this->game->gamestate->setPlayersMultiactive($manual, NextMultiplayers::class, true);
    }

    /** @param int $playerId the owner of the house to sow */
    #[PossibleAction]
    function actPlayersSeeding(int $playerId, int $house)
    {
        $this->game->seedingService->recordPick((int) $this->game->getCurrentPlayerId(), $playerId, $house);

        return NextMultiplayers::class;
    }

    /** Zombie level 1: chooses a random house among the allowed ones. */
    function zombie(int $playerId)
    {
        $locations = $this->game->seedingService->availableLocations($playerId);
        $choice = $locations[bga_rand(0, count($locations) - 1)];

        $this->game->seedingService->recordPick($playerId, (int) $choice['playerId'], Board::houseNumber($choice['location']));

        return NextMultiplayers::class;
    }
}
