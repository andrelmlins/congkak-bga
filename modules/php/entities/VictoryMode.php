<?php

namespace Bga\Games\congkak\entities;

enum VictoryMode: string
{
    case NumberOfRounds = 'numberOfRounds';
    case NumberOfSeeds = 'numberOfSeeds';
    case NumberHousesBurned = 'numberHousesBurned';
}
