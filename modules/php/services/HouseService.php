<?php

namespace Bga\Games\congkak\services;

use Bga\Games\congkak\Game;

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

    public function isLocked($playerId, $location)
    {
        $sql = "SELECT house_locked FROM house WHERE house_location = '%s' AND house_player = '%s'";
        $item = $this->game->getObjectFromDB(sprintf($sql, $location, $playerId));

        return $item['house_locked'] == '1';
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

    public function locked($playerId, $location)
    {
        $sql = "UPDATE `house` SET house_locked = 1 WHERE house_location = '%s' AND house_player = '%s'";
        $this->game->DbQuery(sprintf($sql, $location, $playerId));
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

    public function listLockeds()
    {
        $sql = "SELECT * FROM house";
        $list = $this->game->getObjectListFromDB($sql);

        $result = [];

        foreach ($list as $item) {
            if (!array_key_exists($item['house_player'], $result)) {
                $result[$item['house_player']] = [];
            }

            if ($item['house_location'] != 'rumah') {
                $result[$item['house_player']][$item['house_location']] = $item['house_locked'] == '1';
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

        $result = false;

        while (!$result) {
            if ($position == count($sequence)) {
                $position = 0;
                continue;
            }

            $item = $sequence[$position];

            if ($item['location'] == 'rumah' && $item['playerId'] != $currentPlayerId) {
                $position += 1;
                continue;
            }

            if ($this->isLocked($item['playerId'], $item['location'])) {
                $position += 1;
                continue;
            }

            $result = true;
        }

        return $sequence[$position];
    }

    public function countAllSeedsInKampong()
    {
        $sql = "SELECT SUM(house_seeds) seeds FROM house WHERE house_location != 'rumah'";
        $item = $this->game->getObjectFromDB($sql);

        return intval($item['seeds']);
    }

    public function isNewRound()
    {
        $isNewRound = false;
        $players = $this->game->loadPlayersBasicInfos();

        foreach ($players as $player) {
            $sql = "SELECT SUM(house_seeds) seeds FROM house WHERE house_location != 'rumah' AND house_player = '%s'";
            $item = $this->game->getObjectFromDB(sprintf($sql, $player['player_id']));

            if (intval($item['seeds']) == 0) {
                $isNewRound = true;
            }
        }

        return $isNewRound;
    }

    public function isRoundGameEnd()
    {
        $roundSettings = $this->game->optionsService->getRoundSettings();

        if (is_null($roundSettings['total'])) return false;

        return $roundSettings['current'] == $roundSettings['total'];
    }

    public function isGameEnd()
    {
        $isGameEnd = false;
        $players = $this->game->loadPlayersBasicInfos();

        foreach ($players as $player) {
            $sql = "SELECT * FROM house WHERE house_location != 'rumah' AND house_player = '%s' AND house_locked = 1";
            $listLockeds = $this->game->getObjectListFromDB(sprintf($sql, $player['player_id']));

            if (intval($listLockeds) == 7) {
                $isGameEnd = true;
            }
        }

        return $isGameEnd;
    }

    public function lowestSeededPlayer()
    {
        $count = 1000;
        $playerId = null;
        $players = $this->game->loadPlayersBasicInfos();

        foreach ($players as $player) {
            $sql = "SELECT SUM(house_seeds) seeds FROM house WHERE house_player = '%s'";
            $item = $this->game->getObjectFromDB(sprintf($sql, $player['player_id']));

            if ($item['seeds'] < $count) {
                $playerId = $player['player_id'];
                $count = $item['seeds'];
            } else if ($item['seeds'] == $count && $player['player_no'] == 1) {
                $playerId = $player['player_id'];
            }
        }

        return $playerId;
    }
}
