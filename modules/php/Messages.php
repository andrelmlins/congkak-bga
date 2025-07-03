<?php

namespace Bga\Games\congkak;

class Messages
{
    static string $InvalidAction = '';
    static string $PlayersSeeding = '';
    static string $EndSeeding = '';
    static string $EndSeedingWithSeeds = '';
    static string $EndSeedingRumah = '';
    static string $EndSeedingInOpponent = '';

    static function initMessages()
    {
        Messages::$InvalidAction = clienttranslate('Invalid action');
        Messages::$PlayersSeeding = clienttranslate('Players perform their seeding');
        Messages::$EndSeeding = clienttranslate('${player_name} finishes sowing on a seedless house on his side and receives all the seeds from that house and the corresponding house from his opponent.');
        Messages::$EndSeedingWithSeeds = clienttranslate('${player_name} finishes sowing in a house with seeds and goes to play again with those seedss');
        Messages::$EndSeedingRumah = clienttranslate('${player_name} finishes sowing in his warehouse and goes to play again.');
        Messages::$EndSeedingInOpponent = clienttranslate('${player_name} ends the sowing on a seedless house on his opponent\'s side');
    }
}
