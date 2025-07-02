<?php

namespace Bga\Games\congkak;

trait Constants
{
    public function startConstants() {}

    public function getLocalCurrentPlayerId($bReturnNullIfNotLogged = false)
    {
        return $this->getCurrentPlayerId($bReturnNullIfNotLogged);
    }

    public function localActiveNextPlayer()
    {
        return $this->activeNextPlayer();
    }
}
