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

use Bga\GameFramework\Table;
use Bga\Games\congkak\entities\GameMode;
use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\Managers\HouseManager;
use Bga\Games\congkak\Managers\PlayerManager;
use Bga\Games\congkak\Managers\StatsManager;
use Bga\Games\congkak\Managers\StepManager;
use Bga\Games\congkak\Managers\OptionsManager;
use Bga\Games\congkak\Managers\SeedingManager;
use Bga\Games\congkak\States\NewRound;

class Game extends Table
{
    /** The reserved state that ends the game. */
    public const ST_END_GAME = 99;

    public HouseManager $houseManager;

    public PlayerManager $playerManager;

    public StepManager $stepManager;

    public StatsManager $statsManager;

    public OptionsManager $optionsManager;

    public SeedingManager $seedingManager;

    public function __construct()
    {
        parent::__construct();

        Messages::initMessages();

        $this->houseManager = new HouseManager($this);

        $this->playerManager = new PlayerManager($this);

        $this->stepManager = new StepManager($this);

        $this->statsManager = new StatsManager($this);

        $this->optionsManager = new OptionsManager($this);

        $this->seedingManager = new SeedingManager($this);
    }

    public function getGameProgression()
    {
        $gameMode = $this->optionsManager->getGameMode();
        $roundSettings = $this->optionsManager->getRoundSettings();

        if ($gameMode != GameMode::TotalBurn) {
            $total = 98;
            $current = $total - $this->houseManager->countAllSeedsInKampong();
            $percentBase = (1 / $roundSettings['total']) * 100;
            $percentCurrent = (($roundSettings['current'] - 1) / $roundSettings['total']) * 100;

            return round($percentCurrent + (($percentBase * $current) / $total));
        } else {
            $total = 7;
            $current = $this->houseManager->countMaxLockeds();

            return round((100 * $current) / $total);
        }
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
        $result["houseSequence"] = $this->houseManager->sequence($currentPlayerId);
        $result["houseList"] = $this->houseManager->list();
        $result["playerPosition"] = $this->playerManager->getPlayerOrder($currentPlayerId);
        $result["opponentPlayerId"] = $this->playerManager->getOpponnetId($currentPlayerId);
        $result["houseListLockeds"] = $this->houseManager->listLockeds();
        $result["roundDetails"] = $this->optionsManager->getRoundSettings();

        return $result;
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

        $this->DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        $this->statsManager->setup($players);
        $this->houseManager->create();

        $this->activeNextPlayer();

        return NewRound::class;
    }
}
