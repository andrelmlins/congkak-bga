<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * congkak implementation : © André Lins andrelucas01@hotmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\congkak;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    use Constants;

    public HouseService $houseService;

    public PlayerService $playerService;

    public StepService $stepService;

    public StatsService $statsService;

    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([]);

        Messages::initMessages();
        $this->startConstants();

        $this->houseService = new HouseService($this);

        $this->playerService = new PlayerService($this);

        $this->stepService = new StepService($this);

        $this->statsService = new StatsService($this);
    }

    public function argPlayersSeeding()
    {
        return $this->stepService->argPlayersSeeding();
    }

    public function argPlayerSeeding()
    {
        return $this->stepService->argPlayerSeeding();
    }

    public function actPlayersSeeding(string $playerId, int $house)
    {
        $this->stepService->actPlayersSeeding($playerId, $house);
    }

    public function actPlayerSeeding(string $playerId, int $house)
    {
        $this->stepService->actPlayerSeeding($playerId, $house);
    }

    public function stNewRound()
    {
        $this->stepService->stNewRound();
    }

    public function stAllPlayers()
    {
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stNextMultiplayers()
    {
        $this->stepService->stNextMultiplayers();
    }

    public function stNextPlayer(): void
    {
        $this->stepService->stNextPlayer();
    }

    public function getGameProgression()
    {
        return ($this->houseService->countAllSeedsInKampong() * 100) / 98;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }


    protected function getAllDatas(): array
    {
        $currentPlayerId = (int) $this->getCurrentPlayerId();

        $result = [];
        $result["players"] = $this->getCollectionFromDb("SELECT `player_id` `id`, `player_score` `score` FROM `player`");
        $result["houseSequence"] = $this->houseService->sequence($currentPlayerId);
        $result["houseList"] = $this->houseService->list();
        $result["playerPosition"] = $this->playerService->getPlayerOrder($currentPlayerId);
        $result["opponentPlayerId"] = $this->playerService->getOpponnetId($currentPlayerId);
        $result["houseListLockeds"] = $this->houseService->listLockeds();

        return $result;
    }

    protected function getGameName()
    {
        return "congkak";
    }

    protected function setupNewGame($players, $options = [])
    {
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        $this->statsService->setup($players);
        $this->houseService->create();

        $this->activeNextPlayer();
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default: {
                        $this->gamestate->nextState("zombiePass");
                        break;
                    }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
