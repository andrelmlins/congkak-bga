<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\entities\VictoryMode;
use Bga\Games\congkak\Game;

class PlayerService extends \APP_GameClass
{
    public Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function getOpponnetId($playerId)
    {
        $players = $this->game->loadPlayersBasicInfos();

        $opponentPlayerId = null;

        foreach ($players as $player) {
            if ($player['player_id'] != $playerId) {
                $opponentPlayerId = $player['player_id'];
            }
        }

        return $opponentPlayerId;
    }

    public function getPlayerOrder($currentPlayerId)
    {
        $result = $this->game->getCollectionFromDb("SELECT player_id id, player_no position FROM player ORDER BY player_no");

        $playerIds = array_map(fn($player): string => $player['position'] - 1, $result);
        $playerPosition = array_values(array_map(fn($player): string => $player['id'], $result));

        $resultPlayerIds = [$playerPosition[0]];
        $position = 0;

        if (array_key_exists($currentPlayerId, $playerIds)) {
            $resultPlayerIds = [$currentPlayerId];
            $position = $playerIds[$currentPlayerId];
        }

        for ($i = 0; $i < count($result) - 1; $i++) {
            $position = $position == (count($result) - 1) ? 0 : $position + 1;
            $resultPlayerIds[] = intval($playerPosition[$position]);
        }

        return $resultPlayerIds;
    }

    function updateScore($playerId)
    {
        $victoryMode = $this->game->optionsService->getVictoryMode();
        $score = 0;

        if ($victoryMode == VictoryMode::NumberOfSeeds) {
            $seeds = $this->game->houseService->list();
            $score = $seeds[$playerId]['rumah'];
        } else if ($victoryMode == VictoryMode::NumberOfRounds) {
            $sql = "SELECT player_round_wins FROM player WHERE player_id = '%s'";
            $result = $this->game->getObjectFromDB(sprintf($sql, $playerId));

            $score = intval($result['player_round_wins']);
        } else if ($victoryMode == VictoryMode::NumberHousesBurned) {
            $sql = "SELECT count(*) lockeds FROM house WHERE house_player = '%s' AND house_locked = '1'";
            $result = $this->game->getObjectFromDB(sprintf($sql, $playerId));

            $score = intval($result['lockeds']);
        }

        $sql = "UPDATE player SET player_score = %s WHERE player_id = '%s'";
        $this->game->DbQuery(sprintf($sql, $score, $playerId));

        $this->game->notifyAllPlayers('score', '', ['score' => $score, 'playerId' => $playerId]);
    }

    function setRoundWin()
    {
        $houses = $this->game->houseService->list();
        $winPlayersIds = [];
        $maxSeeds = 0;

        foreach ($houses as $playerId => $house) {
            if ($house['rumah'] > $maxSeeds) {
                $maxSeeds = $house['rumah'];
                $winPlayersIds = [$playerId];
            } else if ($house['rumah'] == $maxSeeds) {
                $winPlayersIds[] = $playerId;
            }
        }

        foreach ($winPlayersIds as $playerId) {
            $sql = "UPDATE player SET player_round_wins = player_round_wins + 1 WHERE player_id = '%s'";
            $this->game->DbQuery(sprintf($sql, $playerId));

            $this->game->notifyAllPlayers('winRound', Messages::$WinRound, [
                'player_name' => $this->game->getPlayerNameById($playerId)
            ]);

            $this->game->statsService->incRoundsWon($playerId);
        }
    }
}
