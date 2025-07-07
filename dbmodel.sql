-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- congkak implementation : © André Lins andrelucas01@hotmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----
CREATE TABLE IF NOT EXISTS `house` (
    `house_location` varchar(10) NOT NULL,
    `house_player` varchar(16) NOT NULL,
    `house_seeds` int(11) unsigned NOT NULL,
    `house_locked` TINYINT unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`house_location`, `house_player`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;