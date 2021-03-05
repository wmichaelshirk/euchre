<?php
/**
*------
* BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel 
* Colin <ecolin@boardgamearena.com>
* Euchre implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
*
* This code has been produced on the BGA studio platform for use on 
* https://boardgamearena.com.  See http://en.doc.boardgamearena.com/Studio for
* more information.
* -----
*/


class action_euchrenisterius extends APP_GameAction {

    // Constructor: please do not modify
    public function __default() {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "euchrenisterius_euchrenisterius";
            self::trace("Complete reinitialization of board game");
        }
    }

    // TODO: defines your action entry points there

    public function playCard() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->playCard($card_id);
        self::ajaxResponse();
    }

    public function acceptOrPass() {
        self::setAjaxMode();
        $acceptOrPass = self::getArg("bid", AT_alphanum, true);
        $this->game->acceptOrPass($acceptOrPass);
        self::ajaxResponse();
    }
  

}


