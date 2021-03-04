{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel 
-- Colin <ecolin@boardgamearena.com>
-- euchrenisterius implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on 
-- http://boardgamearena.com. See http://en.boardgamearena.com/#!doc/Studio for
-- more information.
-------

    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<div id="mainview" class="mainview">

    <div id="special_cards" class="special_cards">
		<div id="visibleCard" class="visibleCard whiteblock">
			<h3>{TRUMP_CARD}</h3>
			<div id="trumpCard" class="trumpCard"></div>
		</div>

		<div id="trumpBlock" class="trumpBlock whiteblock">
			<h3>{TRUMP_SUIT}</h3>
			<div id="trumpSuit" class="trumpSuit"></div>
		</div>	
        
        <div id="gameProgression" class="gameProgression whiteblock">
            <div id="handinfo" class="handinfo">Game Progression</div>
            <div id="handcount" class="handcount"></div>
        </div>
	</div>

    <div id="playertables">

        <!-- BEGIN player -->
        <div id="playertable_{PLAYER_ID}" 
                class="playertable playertable_{DIR}">

            <div id="playertable_avatar_{PLAYER_ID}" 
                    class="playerTables__avatar-wrapper">
				<div class="playerTables__avatar" 
                        style="background-image: url({PLAYER_AVATAR_URL_184})">
                </div>
				<!-- <div class="playerTables__bubble"></div> -->
				<div class="playerTables__name" 
                        style="background-color:#{PLAYER_COLOR}b0">
                    <span>{PLAYER_NAME}</span>
                </div>
			</div>
            <div class="playertabletrickswonicon"></div>
            <div id="playertabletrickswonvalue_{PLAYER_ID}" class="playertabletrickswonvalue"></div>
        </div>

        <div class="playertablecard playertablecard_{DIR}" id="playertablecard_{PLAYER_ID}"></div>

        <!-- END player -->
       

        <div id="declarer_icon" class="declarer_icon"></div>
        <div id="accepter_icon" class="accepter_icon"></div>
        <div id="dealer_icon" class="dealer_icon"></div>

        <div id="turn_order">↻</div>
    </div>

	<div id="myhand_wrap" class="whiteblock">
        <h3>{MY_HAND}</h3>
        <div id="myhand">
        </div>
    </div>
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_cardontable = '<div class="cardontable" id="cardontable_${player_id}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_trumpcardontable = '<div class = "cardontable" id="trumpcardontable" style="background-position:-${x}px -${y}px"></div>';
var jstpl_trumpsymbolontable = '<div class ="symbolontable_${suit}" id="trumpsymbolontable"></div>';
var jstpl_contractontable = '<div id="contractontable" style="display: flex;justify-content: center;align-items: center;height: 40px;"><strong>${contract}</strong></div>';

</script>  
{OVERALL_GAME_FOOTER}
