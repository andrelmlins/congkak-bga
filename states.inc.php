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
 * states.inc.php
 *
 * congkak game states description
 *
 */

$machinestates = [
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => 2]
    ),

    2 => [
        "name" => "newRound",
        "description" => '',
        "type" => "game",
        "action" => "stNewRound",
        "transitions" => ["playersSeeding" => 10, "playerSeeding" => 11, "gameEnd" => 99]
    ],

    10 => [
        "name" => "playersSeeding",
        "description" => clienttranslate('Waiting for other players to perform their action'),
        "descriptionmyturn" => clienttranslate('${you} must select a house to sow'),
        "type" => "multipleactiveplayer",
        "action" => "stPlayersSeeding",
        "args" => "argPlayersSeeding",
        "possibleactions" => ["actPlayersSeeding"],
        "transitions" => ["nextMultiplayers" => 20]
    ],

    11 => [
        "name" => "playerSeeding",
        "description" => clienttranslate('${actplayer} must select a house to sow'),
        "descriptionmyturn" => clienttranslate('${you} must select a house to sow'),
        "type" => "activeplayer",
        "action" => "stPlayerSeeding",
        "args" => "argPlayerSeeding",
        "possibleactions" => ["actPlayerSeeding"],
        "transitions" => ["nextPlayer" => 21, "playerSeeding" => 11, "newRound" => 2]
    ],

    20 => [
        "name" => "nextMultiplayers",
        "description" => '',
        "type" => "game",
        "updateGameProgression" => true,
        "action" => "stNextMultiplayers",
        "transitions" => ["playersSeeding" => 10, "playerSeeding" => 11]
    ],

    21 => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => ["playerSeeding" => 11, "newRound" => 2]
    ],

    99 => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];
