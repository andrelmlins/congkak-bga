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

    public function update($playerId, $location, int $seeds)
    {
        $sql = "UPDATE `house` SET house_seeds = %s WHERE house_location = '%s' AND house_player = '%s'";
        $this->game->DbQuery(sprintf($sql, $seeds, $location, $playerId));
    }

    public function updateInc($playerId, $location, int $seeds)
    {
        $sql = "UPDATE `house` SET house_seeds = house_seeds + %s WHERE house_location = '%s' AND house_player = '%s'";
        $this->game->DbQuery(sprintf($sql, $seeds, $location, $playerId));
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
        $anotherPlayerId = $this->game->playerService->getOpponnetId($playerId);

        $result = [];

        for ($i = 7; $i > 0; $i--) {
            $result[] = ['playerId' => strval($playerId), 'location' => "kampong_{$i}"];
        }
        $result[] = ['playerId' => strval($playerId), 'location' => 'rumah'];

        for ($i = 7; $i > 0; $i--) {
            $result[] = ['playerId' => $anotherPlayerId, 'location' => "kampong_{$i}"];
        }
        $result[] = ['playerId' => $anotherPlayerId, 'location' => 'rumah'];

        return $result;
    }

    public function getNextHouse($currentPlayerId, $playerId, $house)
    {
        $sequence = $this->sequence($currentPlayerId);
        $position = array_search(['playerId' => $playerId, 'location' => $house], $sequence) + 1;

        if ($position == count($sequence)) {
            $position = 0;
        }

        $item = $sequence[$position];

        if ($item['location'] == 'rumah' && $item['playerId'] != $currentPlayerId) {
            $position += 1;

            if ($position == count($sequence)) {
                $position = 0;
            }

            $item = $sequence[$position];
        }

        return $item;
    }

    public function countAllSeedsInKampong()
    {
        $sql = "SELECT SUM(house_seeds) seeds FROM house WHERE house_location != 'rumah'";
        $item = $this->game->getObjectFromDB($sql);

        return intval($item['seeds']);
    }

    public function isGameEnd()
    {
        $isGameEnd = false;
        $players = $this->game->loadPlayersBasicInfos();

        foreach ($players as $player) {
            $sql = "SELECT SUM(house_seeds) seeds FROM house WHERE house_location != 'rumah' AND house_player = '%s'";
            $item = $this->game->getObjectFromDB(sprintf($sql, $player['player_id']));

            if (intval($item['seeds']) == 0) {
                $isGameEnd = true;
            }
        }

        return $isGameEnd;
    }
}
