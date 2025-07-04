<?php

namespace Bga\Games\congkak;

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
        $seeds = $this->game->houseService->list();
        $score = $seeds[$playerId]['rumah'];

        $sql = "UPDATE player SET player_score = %s WHERE player_id = '%s'";
        $this->game->DbQuery(sprintf($sql, $score, $playerId));

        $this->game->notifyAllPlayers('score', '', [
            'score' => $score,
            'playerId' => $playerId,
        ]);

        $this->game->statsService->setSeedsStorehouse($score, $playerId);
    }
}
