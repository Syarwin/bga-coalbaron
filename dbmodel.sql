-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- CoalBaron implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Mathieu Chatrain <EMAIL>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----
-- dbmodel.sql

CREATE TABLE IF NOT EXISTS `meeples` (
  `meeple_id` int(5) NOT NULL AUTO_INCREMENT,
  `meeple_state` int(10) DEFAULT 0,
  `meeple_location` varchar(32) NOT NULL,
  `player_id` int(10) NULL,
  `type` VARCHAR(32) NULL,
  PRIMARY KEY (`meeple_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `cards` (
  `card_id` int(1) NOT NULL AUTO_INCREMENT,
  `card_state` int(10) DEFAULT 0,
  `card_location` varchar(32) NOT NULL,
  `type` int(10) DEFAULT 0 NOT NULL,
  `player_id` int(10) NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `tiles` (
  `tile_id` int(1) NOT NULL AUTO_INCREMENT,
  `tile_state` int(10) DEFAULT 0,
  `tile_location` varchar(32) NOT NULL,
  `type` int(10) DEFAULT 0 NOT NULL,
  `player_id` int(10) NULL,
  `x` int(10) NULL,
  `y` int(10) NULL,
  PRIMARY KEY (`tile_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

--ADD a money count to each player
ALTER TABLE `player` ADD `money` INT(3) DEFAULT 0;


-- CORE TABLES --
CREATE TABLE IF NOT EXISTS `global_variables` (
  `name` varchar(255) NOT NULL,
  `value` JSON,
  PRIMARY KEY (`name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) NOT NULL,
  `pref_id` int(10) NOT NULL,
  `pref_value` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `move_id` int(10) NOT NULL,
  `table` varchar(32) NOT NULL,
  `primary` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `affected` JSON,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
ALTER TABLE `gamelog`
ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;