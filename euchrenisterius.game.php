<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel
  * Colin <ecolin@boardgamearena.com>
  * Euchre implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
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
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
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
			// spade, heart, diamond, club
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

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

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
    function getGameProgression()
    {
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



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in euchrenisterius.action.php)
    */

    /*

    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' );

        $player_id = self::getActivePlayerId();

        // Add your game logic to play a card there
        ...

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );

    }

    */


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
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
		self::setGameStateInitialValue('trumpSuit', 0);
        self::setGameStateInitialValue('suitLed', 0);
        self::setGameStateInitialValue('trickCount', 0);
        
        // Announce start of hand.
        $dealer = self::getGameStateValue('dealerId');
        $eldest = self::getGameStateValue('eldestId');
        $turnUp = $this->cards->getCardOnTop('deck');
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


// stEndHand

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn( $state, $active_player )
    {
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

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
