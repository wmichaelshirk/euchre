<?php
/**
*------
* BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel 
* Colin <ecolin@boardgamearena.com> 
* Euchre implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
*
* This code has been produced on the BGA studio platform for use on 
* http://boardgamearena.com.  See http://en.boardgamearena.com/#!doc/Studio for
*  more information.
* -----
*/

require_once( APP_BASE_PATH."view/common/game.view.php" );

class view_euchrenisterius_euchrenisterius extends game_view {

    function getGameName() {
        return "euchrenisterius";
    }    

    function build_page($viewArgs) {		
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );
        
        $template = self::getGameName() . "_" . self::getGameName();

        // Arrange players so that I am on south
        $player_to_dir = $this->game->getPlayerToDirections();

        
        // this will inflate our player block with actual players data
        $this->page->begin_block($template, "player");
        foreach ( $player_to_dir as $player_id => $dir ) {
            $this->page->insert_block("player", [
                    "PLAYER_ID" => $player_id,
                    "PLAYER_NAME" => $players[$player_id]['player_name'],
                    "PLAYER_COLOR" => $players[$player_id]['player_color'],
                    "DIR" => $dir,
                    'PLAYER_AVATAR_URL_184' => $this->getPlayerAvatar(
                        $players[$player_id],
                        '184'
                    ),
                ]);
        }
        // this will make our My Hand text translatable
        $this->tpl['MY_HAND'] = self::_("My hand");

        // Testing
        $this->tpl['TRUMP_CARD'] = self::_("Trump Card");
        $this->tpl['TRUMP_SUIT'] = self::_("Trump Suit");
        $this->tpl['GAME_CONTRACT'] = self::_("Contract");
      }
      
      /* Getting the avatar (copied from Coinche code) */
      private function getPlayerAvatar($player, $size) {
		$avatarPlayerId = (string) $player['player_id'];

		// Zero means "0", otherwise, length of the string from the start
		$lengthMap = [
			8 => [0, 2, 5],
			7 => [0, 1, 4],
			6 => [0, 0, 3],
			5 => [0, 0, 1], // ? no case found
			4 => [0, 0, 1],
			3 => [0, 0, 0],
			2 => [0, 0, 0],
			1 => [0, 0, 0],
		];

		$length = strlen($avatarPlayerId);
		if (!isset($lengthMap[$length])) {
			return null;
		}
		$len0 = $lengthMap[$length][0];
		$len1 = $lengthMap[$length][1];
		$len2 = $lengthMap[$length][2];

		$avatarUrl = sprintf(
			'https://x.boardgamearena.net/data/avatar/%s/%s/%s/%s_%s.jpg?h=%s',
			$len0 === 0 ? '0' : substr($avatarPlayerId, 0, $len0),
			$len1 === 0 ? '0' : substr($avatarPlayerId, 0, $len1),
			$len2 === 0 ? '0' : substr($avatarPlayerId, 0, $len2),
			$avatarPlayerId,
			$size,
			$player['player_avatar']
		);

		return $avatarUrl;
    }
}
