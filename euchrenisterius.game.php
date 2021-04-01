<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel
  * Colin <ecolin@boardgamearena.com>
  * Euchre implementation : © W Michael Shirk <wmichaelshirk@gmail.com> &
  *                           George Witty <jimblefredberry@gmail.com>
  *
  * This code has been produced on the BGA studio platform for use on
  * http://boardgamearena.com.  See http://en.boardgamearena.com/#!doc/Studio
  * for more information.
  * -----
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class euchrenisterius extends Table {

	function __construct( ) {
        parent::__construct();

        self::initGameStateLabels([
            'dealerId' => 10,
            'eldestId' => 11,
            'declarerId' => 12,

            'trumpSuit' => 13,
            'turnUpSuit' => 14,
            'turnUpValue' => 15,

            'suitLed' => 16,
            'trickCount' => 17,
            'trickCount' => 18,

            // Options:
            "targetScore" => 100,
            "deckSize" => 101,
            "useJoker" => 102,

			// Options
            // 'stickTheDealer'
            // 'canadianLoner
            // 'callAce' ?
        ]);

        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
	}

    // Used for translations and stuff. Please do not modify.
    protected function getGameName() {
        return "euchrenisterius";
    }

    /*
        This method is called only once, when a new game is launched. In this
        method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = []) {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database
        // (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('".$player_id."','$color','".$player['player_canal']
                ."','".addslashes( $player['player_name'] )."','"
                .addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        // Maybe get rid of the above

        // Sort players

        // Initialize gamevalues
        $firstDealerId = array_rand($players, 1);
		self::setGameStateInitialValue('dealerId', $firstDealerId);
		self::setGameStateInitialValue('eldestId', self::getPlayerAfter($firstDealerId));
        self::setGameStateInitialValue('declarerId', 0);
		self::setGameStateInitialValue('trumpSuit', 0);
        self::setGameStateInitialValue('turnUpSuit', 0);
        self::setGameStateInitialValue('turnUpValue', 0);
        self::setGameStateInitialValue('suitLed', 0);
        self::setGameStateInitialValue('trickCount', 0);

        // build deck
        $cards = [];

        // Joker
        if (self::getGameStateValue('useJoker') == 1) {
            $cards[] = [
                'type' => 5,
                'type_arg' => 1,
                'nbr' => 1,
            ];
        }
        // rest of deck.
        $startingRank = self::getGameStateValue('deckSize');
        foreach ($this->suits as $suitId => $suit) {
			// spade, heart, club, diamond
			for ($value = $startingRank; $value <= 14; $value++) {
				$cards[] = [
					'type' => $suitId,
					'type_arg' => $value,
					'nbr' => 1,
				];
			}
		}
		$this->cards->createCards($cards, 'deck');

        // Initialize game Statistics
        // TODO: Game statistics

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas() {
        $result = [];

        // !! We must only return informations visible by this player !!
        $current_player_id = self::getCurrentPlayerId();

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table
        // in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);
        $result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');
        $result['trumpSuit'] = self::getGameStateValue('trumpSuit');
        $result['turnUpSuit'] = self::getGameStateValue('turnUpSuit');
        $result['turnUpValue'] = self::getGameStateValue('turnUpValue');

        $result['dealerId'] = self::getGameStateValue('dealerId');
        $result['suits'] = $this->suits;
        $result['ranks'] = $this->ranks;
        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression () {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */


    function getPlayerToDirections() {
        $result = [];

        $players = self::loadPlayersBasicInfos();
        $nextPlayer = self::createNextPlayerTable(array_keys($players));

        $current_player = self::getCurrentPlayerId();

        $directions = ['S', 'W', 'N', 'E'];

        if (!isset($nextPlayer[$current_player])) {
            // Spectator mode: take any player for south
            $player_id = $nextPlayer[0];
            $result[$player_id] = array_shift($directions);
        } else {
            // Normal mode: current player is on south
            $player_id = $current_player;
            $result[$player_id] = array_shift($directions);
        }

        while(count($directions) > 0) {
            $player_id = $nextPlayer[$player_id];
            $result[ $player_id ] = array_shift($directions);
        }
        return $result;
    }

    function getPlayerName($player_id) {
        $players = self::loadPlayersBasicInfos();
        return $players[$player_id]['player_name'];
    }

    function assertCardPlay($cardId) {
        $playerId = self::getActivePlayerId();
        $playerHand = $this->cards->getCardsInLocation('hand', $playerId);

        $isInHand = false;
        $suitLed = self::getGameStateValue('suitLed');
        $trumpSuit = self::getGameStateValue('trumpSuit');
        $atLeastOneOfSuitLed = false;

        foreach ($playerHand as $currentCard) {
            $suit = self::getSuit($currentCard);

            if ($currentCard['id'] == $cardId) {
                $isInHand = true;
                $card = $currentCard;
            }
            if ($suit == $suitLed) {
                $atLeastOneOfSuitLed = true;
            }
        }

        if (!$isInHand) {
            throw new BgaUserException(self::_("This card is not in your hand"));
        }

        if ($suitLed != 0) {
            if (self::getSuit($card) != $suitLed) {
                // The card does not match the suit led, and
                // the player has at least one card of the needed suit
                if ($atLeastOneOfSuitLed) {
                    $suitName = $this->suits[$suitLed]['nametr'];
                    if ($suitLed == $trumpSuit) {
                        $suitName = _("Trump");
                    }
                    throw new BgaUserException(sprintf(self::_("You must play a %s"),
                        $this->suits[$suitLed]['nametr']), true);
                }
            }
        }
    }

    private function getPossibleCardsToPlay($playerId) {
		// Loop the player hand, stopping at the first card which can be played
		$playerCards = $this->cards->getCardsInLocation('hand', $playerId);
		$possibleCards = [];
		foreach ($playerCards as $playerCard) {
			try {
				$this->assertCardPlay($playerCard['id']);
			} catch (\Exception $e) {
				continue;
			}
			$possibleCards[] = $playerCard;
		}
		return $possibleCards;
    }


    private function getSuit($card) {
        $trumpSuit = self::getGameStateValue('trumpSuit');
        if ($card['type'] == 5) return $trumpSuit;

        $sameColor = ($trumpSuit + 2) % 4;
        if ($sameColor == 0) {
            $sameColor = 4;
        }
        $jack = 11;
        if ($card['type_arg'] == $jack &&
                ($card['type'] == $trumpSuit || $card['type'] == $sameColor)) {
            return $trumpSuit;
        }
        return $card['type'];
    }
    private function getRank($card) {
        if ($card['type'] == 5) return 17;
        $jack = 11;
        $trumpSuit = self::getGameStateValue('trumpSuit');
        if ($card['type_arg'] == $jack && self::getSuit($card) == $trumpSuit) {
            if ($card['type'] == $trumpSuit) {
                return 16;
            }
            return 15;
        }
        return $card['type_arg'];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below
        is called. (note: each method below must match an input method in
        euchrenisterius.action.php)
    */

    function acceptOrPass($acceptOrPass) {
        // Check acceptOrPass action is available
        self::checkAction("acceptOrPass");

        // Get active player id
        $active_player_id = self::getActivePlayerId();

        // Change the game bid level if necessary
        if ($acceptOrPass == 'passTurnUp') {
            // If this player passed, set the relevant field in the database
            self::DbQuery( 'UPDATE player SET has_passed = 1 WHERE player_id = ' . $active_player_id );

            // And notify 
            self::notifyAllPlayers('message', clienttranslate('${player_name} passes'), [
                'player_name' => self::getActivePlayerName()
            ]);

        } else {
            // TODO: Set trump suit and notify about the card being picked up
            $turnUp = $this->cards->getCardOnTop('deck');

            self::notifyAllPlayers('message', clienttranslate('${player_name} orders up the ${card_name}.'), [
                'player_name' => self::getActivePlayerName(),
                'card' => $turnUp,
                'card_name' => ''
            ]);

            // Set the trump suit based on the ordered up card
            self::setGameStateValue('trumpSuit', $turnUp['type']);

            // Set declarer ID
            self::setGameStateValue('declarerId', $active_player_id);
        }

        // Finish acceptOrPass and change state
        $this->gamestate->nextState('acceptOrPass');
    }

    function playCard($card_id) {
        self::checkAction("playCard");
        $playerId = self::getActivePlayerId();

        // Check rules for card play
        $this->assertCardPlay($card_id);

        $this->cards->moveCard($card_id, 'cardsontable', $playerId);

        $currentCard = $this->cards->getCard($card_id);
        $suitLed = self::getGameStateValue('suitLed') ;
        if ($suitLed == 0) {
           self::setGameStateValue('suitLed', self::getSuit($currentCard));
        }

        self::notifyAllPlayers('playCard',
            clienttranslate('${player_name} plays ${card_name}'), [
                'player_name' => self::getActivePlayerName(),
                'player_id' => $playerId,
                'card' => $currentCard,
                'card_name' => '',
            ]
        );

        // Next player
        $this->gamestate->nextState();
    }

    function discard($cards) {
        // Check discard action is possible
        self::checkAction('discard');

        $playerId = self::getCurrentPlayerId();

        $toDiscard = 1;

        // Is the correct number of cards being discarded?
        if( count( $cards ) != 1 ) {
            throw new BgaUserException( self::_('You must discard exactly one card') ); // TODO: Make this dynamic?
        }

        // Move cards to player's trick pile
        $this->cards->moveCards( $cards, 'deck' );

        // Notify player who discarded and remove discarded cards from hand.
        $this->notifyPlayer( $playerId, 'discarded', '', [ 'cards' => $cards ] );

        // Finish exchange and change state
        $this->gamestate->nextState('done');
    }

    function jokerChooseTrump($trumpSuit) {
        // Check chooseTrump action is possible
        self::checkAction ( 'chooseTrump' );

        // Set $trumpSuit to an integer (not sure why but otherwise chooseTrump thinks it's a string)
        $trumpSuit = (int)$trumpSuit;

        // Set the trump suit to the declarer's choice
        self::setGameStateValue('trumpSuit', $trumpSuit);

        $top21cards = $this->cards->getCardsOnTop(21, 'deck');
        $turnUp = $top21cards[20];

        // Deal the cards as normal now the trump suit has been chosen
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $playerId => $player) {
            $cards = $this->cards->pickCards(5, 'deck', $playerId);
            self::notifyPlayer($playerId, 'newHand', '', ['cards' => $cards]);

            // Reset trick count
            $sql = "UPDATE player SET player_tricks=0 WHERE player_id='$playerId'";
            self::DbQuery($sql);
        }

        self::notifyAllPlayers('chooseTrump', clienttranslate('${player_name} chooses ${trumpSuitName} as the trump suit.'), array(
            'i18n' => array('trumpSuitName'),
            'player_name' => self::getActivePlayerName(),
            'trumpSuitName' => $this->suits[$trumpSuit]['name'],
            'trumpSuit' => $trumpSuit
        ));

        // Reset values
        self::setGameStateValue('turnUpValue', $turnUp['type_arg']);
        self::setGameStateValue('suitLed', 0);
        self::setGameStateValue('trickCount', 0);

        $this->gamestate->nextState('done');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see
        "args" property in states.inc.php).
        These methods function is to return some additional information that is
        specific to the current game state.
    */

    function giveExtraTimeToActivePlayer()
    {
        self::giveExtraTime( self::getActivePlayerId() );
    }

    function argPlayerTurn() {
		// On player's turn, list possible cards
		return [
			'_private' => [
				'active' => [
					'possibleCards' => $this->getPossibleCardsToPlay(
						self::getActivePlayerId()
					),
				],
			],
		];
    }

    /*

    Example for game state "MyGameState":

    function argMyGameState() {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
     * 10 - Start a new hand -
     */
    function stNewHand() {
        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, 'deck');
        // Create deck, shuffle it and deal 5
        $this->cards->shuffle('deck');

        // Work out the turn up before dealing in case it is the joker (the trump suit should be chosen without seeing hands)
        // i.e., get the 21st card in the deck for 4 players
        $top21cards = $this->cards->getCardsOnTop(21, 'deck');
        $turnUp = $top21cards[20];

        // Reset turn up values based on the turn up
        self::setGameStateValue('turnUpSuit', $turnUp['type']);
        self::setGameStateValue('turnUpValue', $turnUp['type_arg']);

        // TESTING: Show turn up in log
        self::notifyAllPlayers('message', clienttranslate('TESTING: The turn up will be ${card_name}'), [
            'card' => $turnUp,
            'card_name' => '',
        ]);

        // Announce start of hand.
        $dealer = self::getGameStateValue('dealerId');
        $eldest = self::getGameStateValue('eldestId');
        self::notifyAllPlayers('newDeal', clienttranslate('<hr/>${player_name} deals a new hand and turns the ${card_name}.<hr/>'), [
            'dealer_id' => $dealer,
            'player_name' => self::getPlayerName($dealer),
            'eldest' => $eldest,
            'card' => $turnUp,
            'trumpSuit' => $turnUp['type'],
            'trumpValue' => $turnUp['type_arg'],
            'card_name' => '',
        ]);

        if (self::getRank($turnUp) == 17) {
        // if (1 == 1) { // For testing joker transitions
            // If the turn up is a joker, need to move to the Joker state
            $this->gamestate->changeActivePlayer($dealer);
            $this->gamestate->nextState('joker');
        } else {

            // Otherwise, we carry on and deal the cards

            $players = self::loadPlayersBasicInfos();
            foreach ($players as $playerId => $player) {
                $cards = $this->cards->pickCards(5, 'deck', $playerId);
                self::notifyPlayer($playerId, 'newHand', '', ['cards' => $cards]);

                // Reset trick count
                $sql = "UPDATE player SET player_tricks=0 WHERE player_id='$playerId'";
                self::DbQuery($sql);
            }

            // Reset values to card on top of deck now that cards have been dealt
            $turnUp = $this->cards->getCardOnTop('deck');
            self::setGameStateValue('turnUpSuit', $turnUp['type']);
            self::setGameStateValue('turnUpValue', $turnUp['type_arg']);
            self::setGameStateValue('suitLed', 0);
            self::setGameStateValue('trickCount', 0);

            $this->gamestate->changeActivePlayer($eldest);
            $this->gamestate->nextState('noJoker');
        }
    }

    function stNextPlayerToAccept() {
        $standing = self::getCollectionFromDb( 'SELECT player_id FROM player WHERE has_passed = 0' );
        $count = count( $standing );
        // $bid_level = self::getGameStateValue('bid_level');
        // $forehand_id = self::getGameStateValue('forehand_id');
        $dealerId = self::getGameStateValue('dealerId');
        $declarerId = self::getGameStateValue('declarerId');

        if ($declarerId != 0) {
            // If a declarer has been chosen then set the active player to the dealer who needs to discard
            $this->gamestate->changeActivePlayer( $dealerId );

            // Move to the state of discarding
            $this->gamestate->nextState('done');
            return;
        } else if ( $count == 0 ) {
            // Everyone passed, so turn over turn up and move to new state

            // TODO: Need to notify and turn down turn up
            self::setGameStateValue('turnUpSuit', 0);
            self::setGameStateValue('turnUpValue', 15); //TESTING
            self::notifyAllPlayers('allPassed', clienttranslate('All players passed.'), array(

            ));

            self::activeNextPlayer();
            $this->gamestate->nextState( 'allRefusedTurnUp' );
            return;
        } else {
            // Otherwise just move on to the next player to bid
            self::activeNextPlayer();
            $this->gamestate->nextState('nextToAccept');
            return;
        }
    }

    function stDiscard() {
        // Give extra time to the active player
        self::giveExtraTime( self::getActivePlayerId() );

        $activePlayerId = self::getActivePlayerId();

        $cards = $this->cards->pickCards(1, 'deck', $activePlayerId);
        
        // Notify player about his cards
        self::notifyPlayer($activePlayerId, 'pickUpCard', '', array (
            'cards' => $cards,
            'trumpSuit' => self::getGameStateValue('trumpSuit')
        ));
    }

    /*
     * 29 - Start the play, eldest leads
     */
    function stEldestLeads() {
        $this->gamestate->changeActivePlayer(self::getGameStateValue('eldestId'));
        $this->gamestate->nextState();
    }

    /*
     * 30 - Start a new trick -
     * Clear the suit led; set the next player to the previous winner.
     */
    function stNewTrick() {
        self::setGameStateValue('suitLed', 0);
        $this->gamestate->nextState();
    }

    /*
     * 31 - Player turn -
     * Send the signal that a check for automatic play can be done on JS
     * The player will automatically play if he selected one card before
     * (an error will be displayed if invalid)
     */
    function stPlayerTurn() {
		$playerId = self::getActivePlayerId();
        self::notifyPlayer($playerId, 'checkForAutomaticPlay', '', []);
    }


    /*
     * 32 - Activate the next [trick?] player,
     * OR end the trick and go to the next trick
     * OR end the hand
     */
    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($this->cards->countCardInLocation('cardsontable') == 4) {
            // This is the end of the trick
            $cardsOnTable = $this->cards->getCardsInLocation('cardsontable');
            $bestValue = 0;
            $trickWinnerId = null;
            $suitLed = self::getGameStateValue('suitLed');
            $trumpSuit = self::getGameStateValue('trumpSuit');

            foreach ($cardsOnTable as $card) {
                $rank = self::getRank($card);
                $suit = self::getSuit($card);
                // If this is the first trump in the trick & trumps were not led
                if ($suit == $trumpSuit && $suitLed != $trumpSuit) {
                    $suitLed = $trumpSuit;
                    $trickWinnerId = $card['location_arg'];
                    $bestValue = $rank;
                }
                // Otherwise:
                // Note: type = card suit
                if ($suit == $suitLed) {
                    if ($trickWinnerId === null || $rank > $bestValue) {
                        $trickWinnerId = $card['location_arg'];
                        // Note: location_arg = player who played this card on table
                        $bestValue = $rank; // Note: type_arg = value of the card
                    }
                }
            }

            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer($trickWinnerId);
            self::giveExtraTime($trickWinnerId);

            // Increment trick counter
            self::incGameStateValue('trickCount', 1);

            // Move all cards to "cardswon" of the given player and update database
            self::DbQuery("UPDATE player SET player_tricks = player_tricks+1 WHERE player_id='$trickWinnerId'");
            $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $trickWinnerId);

            // Get tricks won of best player
            $tricksWon = self::getUniqueValueFromDb(
                "SELECT player_tricks FROM player WHERE player_id='$trickWinnerId'"
            );

            // Notify
            // Note: we use 2 notifications here in order we can pause the display during the first notification
            //  before we move all cards to the winner (during the second)
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick'), [
                'player_id' => $trickWinnerId,
                'player_name' => $players[$trickWinnerId]['player_name'],
                'tricksWon' => $tricksWon
            ]);
            self::notifyAllPlayers('giveAllCardsToPlayer', '', [
                'player_id' => $trickWinnerId
             ]);


            if ($this->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                $this->gamestate->nextState("endHand");
            } else {
                // End of the trick
                $this->gamestate->nextState("nextTrick");
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $playerId = self::activeNextPlayer();
            self::giveExtraTime($playerId);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stEndHand() {
        // TODO: endHand
        // Add up the scores, update everything
        // if someone hit the target option, end the game; otherwise
        // go to the next hand!
        $this->gamestate->nextState('endGame');
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit
        the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this
        player ends appropriately (ex: pass).

        Important: your zombie code will be called when the player leaves the
        game. This action is triggered from the main site and propagated to the
        gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action.
        In your zombieTurn function, you must _never_ use getCurrentPlayerId()
        or getCurrentPlayerName(), otherwise it will fail with a "Not logged"
        error message.
    */

    function zombieTurn($state, $active_player) {
    	$statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

            return;
        }

        throw new feException("Zombie mode not supported at this game state: ".$statename );
    }

////////////////////////////////////////////////////////////////////////////////
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on
        BGA. Once your game is on BGA, this method is called everytime the
        system detects a game running with your old Database scheme.
        In this case, if you change your Database scheme, you just have to apply
        the needed changes in order to update the game database and allow the
        game to continue to run with your new version.
    */

    function upgradeTableDb($from_version) {
        // $from_version is the current version of this game database, in
        // numerical form. For example, if the game was running with a release
        // of your game named "140430-1345", $from_version is equal to
        // 1404301345

        // Example:
        // if ( $from_version <= 1404301345 ) {
        //     // ! important ! Use DBPREFIX_<table_name> for all tables

        //     $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //     self::applyDbUpgradeToAllDB( $sql );
        // }
        // if ( $from_version <= 1405061421 ) {
        //     // ! important ! Use DBPREFIX_<table_name> for all tables

        //     $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //     self::applyDbUpgradeToAllDB( $sql );
        // }
        // // Please add your future database scheme changes here

    }
}
