<?php
/**
*------
* BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> &
* Emmanuel Colin <ecolin@boardgamearena.com>
* Euchre implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
*
* This code has been produced on the BGA studio platform for use on
* http://boardgamearena.com. See http://en.boardgamearena.com/#!doc/Studio for
* more information.
* -----
*/

$this->suits = [
    1 => [
        'name' => clienttranslate('Hearts'),
        'nameof' => clienttranslate('of Hearts'),
        'nametr' => self::_('Heart'),
        'symbol' => '&hearts;'
    ],
    
    2 => [
        'name' => clienttranslate('Clubs'),
        'nameof' => clienttranslate('of Clubs'),
        'nametr' => self::_('Club'),
        'symbol' => '&clubs;'
    ],
    
    3 => [
        'name' => clienttranslate('Diamonds'),
        'nameof' => clienttranslate('of Diamonds'),
        'nametr' => self::_('Diamond'),
        'symbol' => '&diams;'
    ],
    
    4 => [
        'name' => clienttranslate('Spades'),
        'nameof' => clienttranslate('of Spades'),
        'nametr' => self::_('Spade'),
        'symbol' => '&spades;'
    ],
];

$this->ranks = [
    0 => '&#9733;',
	7 => '7',
	8 => '8',
	9 => '9',
	10 => '10',
	11 => clienttranslate('J'),
	12 => clienttranslate('Q'),
	13 => clienttranslate('K'),
	14 => clienttranslate('A'),
];