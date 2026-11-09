<?php

namespace Bga\Games\congkak\Managers;

use Bga\Games\congkak\entities\Messages;
use Bga\Games\congkak\entities\VictoryMode;
use Bga\Games\congkak\Game;
use Bga\Games\congkak\Rules\RoundResult;
use Bga\Games\congkak\Rules\Scoring;

class PlayerManager
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

    /** @return array `[playerId => opponentId]` */
    public function opponentMap()
    {
        $result = [];

        foreach ($this->game->loadPlayersBasicInfos() as $player) {
            $result[$player['player_id']] = $this->getOpponnetId($player['player_id']);
        }

        return $result;
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
        $victoryMode = $this->game->optionsManager->getVictoryMode();
        $rumah = 0;
        $roundWins = 0;
        $lockeds = 0;

        if ($victoryMode == VictoryMode::NumberOfSeeds) {
            $seeds = $this->game->houseManager->list();
            $rumah = $seeds[$playerId]['rumah'];
        } else if ($victoryMode == VictoryMode::NumberOfRounds) {
            $sql = "SELECT player_round_wins FROM player WHERE player_id = '%s'";
            $result = $this->game->getObjectFromDB(sprintf($sql, $playerId));

            $roundWins = intval($result['player_round_wins']);
        } else if ($victoryMode == VictoryMode::NumberHousesBurned) {
            $sql = "SELECT count(*) lockeds FROM house WHERE house_player = '%s' AND house_locked = '1'";
            $result = $this->game->getObjectFromDB(sprintf($sql, $playerId));

            $lockeds = intval($result['lockeds']);
        }

        $this->game->bga->playerScore->set((int) $playerId, Scoring::score($victoryMode, $rumah, $roundWins, $lockeds));
    }

    function setRoundWin()
    {
        $rumah = [];

        foreach ($this->game->houseManager->list() as $playerId => $house) {
            $rumah[$playerId] = $house['rumah'];
        }

        foreach (RoundResult::winners($rumah) as $playerId) {
            $sql = "UPDATE player SET player_round_wins = player_round_wins + 1 WHERE player_id = '%s'";
            $this->game->DbQuery(sprintf($sql, $playerId));

            $this->game->bga->notify->all('winRound', Messages::$WinRound, [
                'player_name' => $this->game->getPlayerNameById($playerId)
            ]);

            $this->game->statsManager->incRoundsWon($playerId);
        }
    }
}
