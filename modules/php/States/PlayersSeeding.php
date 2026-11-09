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
            description: clienttranslate('Waiting for other players to perform their action'),
            descriptionMyTurn: clienttranslate('${you} must select a house to sow'),
        );
    }

    /** @return array{locations: array} per player, the houses they may choose */
    function getArgs(): array
    {
        return $this->game->seedingManager->argPlayersSeeding();
    }

    /** Players with a forced house and automatic seeding on are recorded at once, the others are asked. */
    function onEnteringState()
    {
        $manual = $this->game->seedingManager->recordAutomaticPicks();

        $this->game->gamestate->setPlayersMultiactive($manual, NextMultiplayers::class, true);
    }

    /** @param int $playerId the owner of the house to sow */
    #[PossibleAction]
    function actPlayersSeeding(int $playerId, int $house): void
    {
        $this->choose((int) $this->game->getCurrentPlayerId(), $playerId, $house);
    }

    /** Zombie level 1: chooses a random house among the allowed ones. */
    function zombie(int $playerId): void
    {
        $locations = $this->game->seedingManager->availableLocations($playerId);
        $choice = $locations[bga_rand(0, count($locations) - 1)];

        $this->choose($playerId, (int) $choice['playerId'], Board::houseNumber($choice['location']));
    }

    /**
     * Records the choice and takes the player out of the active ones: the framework only goes on to NextMultiplayers when the last one has chosen
     * (returning the state from the action would jump there after the first player).
     */
    private function choose(int $playerId, int $ownerId, int $house): void
    {
        $this->game->seedingManager->recordPick($playerId, $ownerId, $house);

        $this->game->gamestate->setPlayerNonMultiactive($playerId, NextMultiplayers::class);
    }
}
