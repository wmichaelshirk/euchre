
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel
-- Colin <ecolin@boardgamearena.com>
-- Euchre implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on 
-- http://boardgamearena.com.  See http://en.boardgamearena.com/#!doc/Studio for
-- more information.
-- -----

CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- add a field to count the number of tricks a player has collected
ALTER TABLE `player` ADD `player_tricks` int(10) NOT NULL DEFAULT 0 COMMENT 'Number of tricks collected by the player during this hand';

-- add a field to show if a player has passed in the bidding
ALTER TABLE `player` ADD `has_passed` BIT NOT NULL DEFAULT 0;
