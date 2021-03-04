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

            'trumpSuit' => 12,
            'suitLed' => 13,
            'trickCount' => 14

			// Options
            // 'gameLength'
            // 'deckSize'
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
		self::setGameStateInitialValue('trumpSuit', 0);
        self::setGameStateInitialValue('suitLed', 0);
        self::setGameStateInitialValue('trickCount', 0);
        
        // build deck
        // Joker
        $cards = [[
            'type' => 0,
			'type_arg' => 0,
			'nbr' => 0,
        ]]; 
        foreach ($this->suits as $suitId => $suit) {
			// spade, heart, club, diamond
			for ($value = 7; $value <= 14; $value++) {
				//  7, 8, 9, 10, J, Q, K, A
				$cards[] = [
					'type' => $suitId,
					'type_arg' => $value,
					'nbr' => 1,
				];
			}
		}
		$this->cards->createCards($cards, 'deck');

        // Initialize game Statistics
        // TODO

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
        $atLeastOneOfSuitLed = false;
        $card = null;

        foreach ($playerHand as $currentCard) {
            if ($currentCard['id'] == $cardId) {
                $isInHand = true;
                $card = $currentCard;
            }
            if ($currentCard['type'] == $suitLed) {
                $atLeastOneOfSuitLed = true;
            }
        }

        if (!$isInHand) {
            throw new BgaUserException(self::_("This card is not in your hand"));
        }

        if ($suitLed != 0) {
            if ($card['type'] != $suitLed) {
                // The card does not match the suit led, and
                // the player has at least one card of the needed suit
                if ($atLeastOneOfSuitLed) {
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




//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below 
        is called. (note: each method below must match an input method in 
        euchrenisterius.action.php)
    */


    function playCard($card_id) {
        self::checkAction("playCard");
        $playerId = self::getActivePlayerId();

        // Check rules for card play
        $this->assertCardPlay($card_id);

        $this->cards->moveCard($card_id, 'cardsontable', $playerId);

        $currentCard = $this->cards->getCard($card_id);
        $suitLed = self::getGameStateValue('suitLed') ;
        if ($suitLed == 0) {
           self::setGameStateValue('suitLed', $currentCard['type']);
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


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see 
        "args" property in states.inc.php).
        These methods function is to return some additional information that is 
        specific to the current game state.
    */

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
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $playerId => $player) {
            $cards = $this->cards->pickCards(5, 'deck', $playerId);
            self::notifyPlayer($playerId, 'newHand', '', ['cards' => $cards]);

            // Reset trick count
            $sql = "UPDATE player SET player_tricks=0 WHERE player_id='$playerId'";
            self::DbQuery($sql);
        }

        // Reset values
        $turnUp = $this->cards->getCardOnTop('deck');
		self::setGameStateInitialValue('trumpSuit', $turnUp['type']);
        self::setGameStateInitialValue('suitLed', 0);
        self::setGameStateInitialValue('trickCount', 0);
        
        // Announce start of hand.
        $dealer = self::getGameStateValue('dealerId');
        $eldest = self::getGameStateValue('eldestId');
        self::notifyAllPlayers('newDeal', clienttranslate('<hr/>${player_name} deals a new hand and turns the ${card_name}.<hr/>'), [
            'dealer_id' => $dealer,
            'player_name' => self::getPlayerName($dealer),
            'eldest' => $eldest,
            'turnUp' => $turnUp,
            'card_name' => '',
        ]);

        $this->gamestate->changeActivePlayer($eldest);
        $this->gamestate->nextState();
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
            $best_value = 0;
            $trickWinnerId = null;
            $suitLed = self::getGameStateValue('suitLed');
            $trumpSuit = self::getGameStateValue('trumpSuit');
            foreach ($cardsOnTable as $card) {
                // If this is the first trump in the trick and trumps were not led
                if ($card['type'] == $trumpSuit && $suitLed != $trumpSuit) {
                    $suitLed = $trumpSuit;
                    $trickWinnerId = $card['location_arg'];
                    $best_value = $card['type_arg'];
                }
                // Otherwise:
                // Note: type = card color
                if ($card['type'] == $suitLed) {
                    if ($trickWinnerId === null || $card['type_arg'] > $best_value) {
                        $trickWinnerId = $card['location_arg']; 
                        // Note: location_arg = player who played this card on table
                        $best_value = $card['type_arg']; // Note: type_arg = value of the card
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
        // TODO
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
