<?php

namespace Bga\Games\congkak;

class HouseService extends \APP_GameClass
{
    public Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function create()
    {
        $players = $this->game->loadPlayersBasicInfos();

        $sql = "INSERT INTO house (house_location, house_player, house_seeds) VALUES ('%s', '%s', '%s')";

        foreach ($players as $player) {
            for ($i = 1; $i <= 7; $i++) {
                $this->game->DbQuery(sprintf($sql, "kampong_{$i}", $player['player_id'], 7));
            }

            $this->game->DbQuery(sprintf($sql, 'rumah', $player['player_id'], 0));
        }
    }

    public function list()
    {
        $sql = "SELECT * FROM house";
        $list = $this->game->getObjectListFromDB($sql);

        $result = [];

        foreach ($list as $item) {
            if (!array_key_exists($item['house_player'], $result)) {
                $result[$item['house_player']] = ['kampong' => [], 'rumah' => 0];
            }

            if ($item['house_location'] == 'rumah') {
                $result[$item['house_player']]['rumah'] = intval($item['house_seeds']);
            } else {
                $position = intval(str_replace('kampong_', '', $item['house_location']));
                $result[$item['house_player']]['kampong'][$position] = intval($item['house_seeds']);
            }
        }

        return $result;
    }


    public function sequence($playerId)
    {
        $players = $this->game->loadPlayersBasicInfos();
        $anotherPlayerId = null;

        foreach ($players as $player) {
            if ($player['player_id'] != $playerId) {
                $anotherPlayerId = $player['player_id'];
            }
        }

        $result = [];

        for ($i = 7; $i >= 0; $i--) {
            $result[] = ['playerId' => $playerId, 'location' => "kampong_{$i}"];
        }
        $result[] = ['playerId' => $playerId, 'location' => 'rumah'];

        for ($i = 1; $i <= 7; $i++) {
            $result[] = ['playerId' => $anotherPlayerId, 'location' => "kampong_{$i}"];
        }
        $result[] = ['playerId' => $anotherPlayerId, 'location' => 'rumah'];

        return $result;
    }
}
