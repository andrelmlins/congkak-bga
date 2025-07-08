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

use Bga\Games\congkak\entities\GameMode;
use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\services\HouseService;
use Bga\Games\congkak\services\PlayerService;
use Bga\Games\congkak\services\StatsService;
use Bga\Games\congkak\services\StepService;
use Bga\Games\congkak\services\OptionsService;
use Bga\Games\congkak\services\SeedingService;
use Bga\Games\congkak\services\ZombieService;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    use Constants;

    public HouseService $houseService;

    public PlayerService $playerService;

    public StepService $stepService;

    public StatsService $statsService;

    public OptionsService $optionsService;

    public SeedingService $seedingService;

    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            "gameMode" => 100,
            "numberOfRounds" => 101,
            "houseBurnt" => 102,
            "victoryMode" => 103,
            "automaticSeeding" => 110,
        ]);

        Messages::initMessages();
        $this->startConstants();

        $this->houseService = new HouseService($this);

        $this->playerService = new PlayerService($this);

        $this->stepService = new StepService($this);

        $this->statsService = new StatsService($this);

        $this->optionsService = new OptionsService($this);

        $this->seedingService = new SeedingService($this);
    }

    public function argPlayersSeeding()
    {
        return $this->seedingService->argPlayersSeeding();
    }

    public function argPlayerSeeding()
    {
        return $this->seedingService->argPlayerSeeding();
    }

    public function actPlayersSeeding(string $playerId, int $house)
    {
        $this->seedingService->actPlayersSeeding($playerId, $house, null);
    }

    public function actPlayerSeeding(string $playerId, int $house)
    {
        $this->seedingService->actPlayerSeeding($playerId, $house);
    }

    public function stNewRound()
    {
        $this->stepService->stNewRound();
    }

    public function stPlayersSeeding()
    {
        $this->seedingService->stPlayersSeeding();
    }

    public function stNextMultiplayers()
    {
        $this->stepService->stNextMultiplayers();
    }

    public function stNextPlayer(): void
    {
        $this->stepService->stNextPlayer();
    }

    public function stPlayerSeeding()
    {
        $this->seedingService->stPlayerSeeding();
    }

    public function getGameProgression()
    {
        $gameMode = $this->optionsService->getGameMode();
        $roundSettings = $this->optionsService->getRoundSettings();

        if ($gameMode != GameMode::TotalBurn) {
            $total = 98;
            $current = $total - $this->houseService->countAllSeedsInKampong();
            $percentBase = (1 / $roundSettings['total']) * 100;
            $percentCurrent = (($roundSettings['current'] - 1) / $roundSettings['total']) * 100;

            return round($percentCurrent + (($percentBase * $current) / $total));
        } else {
            $total = 7;
            $current = $this->houseService->countMaxLockeds();

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
        $result["houseSequence"] = $this->houseService->sequence($currentPlayerId);
        $result["houseList"] = $this->houseService->list();
        $result["playerPosition"] = $this->playerService->getPlayerOrder($currentPlayerId);
        $result["opponentPlayerId"] = $this->playerService->getOpponnetId($currentPlayerId);
        $result["houseListLockeds"] = $this->houseService->listLockeds();
        $result["roundDetails"] = $this->optionsService->getRoundSettings();

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

    protected function zombieTurn(array $state, int $activePlayerId): void
    {
        $service = new ZombieService($this);
        $service->run($state, $activePlayerId);
    }
}
