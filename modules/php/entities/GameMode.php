<?php

namespace Bga\Games\congkak\entities;

enum GameMode: string
{
    case SingleRound = 'singleRound';
    case FixedRounds = 'fixedRounds';
    case TotalBurn = 'totalBurn';
}
