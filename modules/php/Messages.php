<?php

namespace Bga\Games\congkak;

class Messages
{
    static string $InvalidAction = '';
    static string $PlayerSeeding = '';
    static string $PlayersSeeding = '';
    static string $EndSeeding = '';
    static string $EndSeedingWithSeeds = '';
    static string $EndSeedingRumah = '';
    static string $EndSeedingInOpponent = '';
    static string $MoveRemainingSeeds = '';

    static function initMessages()
    {
        Messages::$InvalidAction = clienttranslate('Invalid action');
        Messages::$PlayerSeeding = clienttranslate('${player_name} performs a seeding');
        Messages::$PlayersSeeding = clienttranslate('Players perform their seeding');
        Messages::$EndSeeding = clienttranslate('${player_name} finishes sowing on a seedless house on his side and receives all the seeds from that house and the corresponding house from his opponent.');
        Messages::$EndSeedingWithSeeds = clienttranslate('${player_name} finishes sowing in a house with seeds and goes to play again with those seeds');
        Messages::$EndSeedingRumah = clienttranslate('${player_name} finishes sowing in his storehouse and goes to play again.');
        Messages::$EndSeedingInOpponent = clienttranslate('${player_name} ends the sowing on a seedless house on his opponent\'s side');
        Messages::$MoveRemainingSeeds = clienttranslate('${player_name} moves his remaining seeds to storehouse');
    }
}
