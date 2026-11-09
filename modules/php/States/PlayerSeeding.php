<?php

declare(strict_types=1);

namespace Bga\Games\congkak\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\congkak\Game;
use Bga\Games\congkak\Rules\Board;

/** One player sows alone (docs/RULES.md, section 6): a free choice, or the house they must go on from. */
class PlayerSeeding extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 11,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must select a house to sow'),
            descriptionMyTurn: clienttranslate('${you} must select a house to sow'),
            updateGameProgression: true,
        );
    }

    /** @return array{locations: array} the houses the active player may choose */
    function getArgs(): array
    {
        return $this->game->seedingManager->argPlayerSeeding();
    }

    /** With automatic seeding on, a forced house is sown without asking. */
    function onEnteringState(int $activePlayerId)
    {
        $pick = $this->game->seedingManager->automaticPick($activePlayerId);

        if ($pick !== null) {
            return $this->actPlayerSeeding($pick['playerId'], $pick['house'], $activePlayerId);
        }

        return null;
    }

    /** @param int $playerId the owner of the house to sow */
    #[PossibleAction]
    function actPlayerSeeding(int $playerId, int $house, int $activePlayerId)
    {
        return $this->game->seedingManager->actPlayerSeeding($playerId, $house, $activePlayerId);
    }

    /** Zombie level 1: sows a random house among the allowed ones. */
    function zombie(int $playerId)
    {
        $locations = $this->game->seedingManager->argPlayerSeeding()['locations'];
        $choice = $locations[bga_rand(0, count($locations) - 1)];

        return $this->actPlayerSeeding((int) $choice['playerId'], Board::houseNumber($choice['location']), $playerId);
    }
}
