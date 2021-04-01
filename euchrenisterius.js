/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel
 * Colin <ecolin@boardgamearena.com>
 * euchrenisterius implementation: © W Michael Shirk <wmichaelshirk@gmail.com> &
 *                                   George Witty <jimblefredberry@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on
 * http://boardgamearena.com. See http://en.boardgamearena.com/#!doc/Studio for
 * more information.
 * -----
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.euchrenisterius", ebg.core.gamegui, {

        constructor: function() {
            this.cardwidth = 72;
            this.cardheight = 96;
            this.trumpSuit = 0;
        },

        /*
        setup:

        This method must set up the game user interface according to current
        game situation specified in parameters.

        The method is called each time the game interface is displayed to a
        player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)

        "gamedatas" argument contains all datas retrieved by your
        "getAllDatas" PHP method.
        */

        setup: function (gamedatas) {
            console.log( "Starting game setup", gamedatas )

            // Setting up player boards
            for (let player_id in gamedatas.players) {
                var player = gamedatas.players[player_id]

                // TODO: Setting up players boards if needed
            }

            this.ranks = gamedatas.ranks
            this.suits = gamedatas.suits

            // Player hand
            this.playerHand = new ebg.stock()
            this.playerHand.create(this, $('myhand'),
                this.cardwidth, this.cardheight)
            this.playerHand.centerItems = true
            this.playerHand.setSelectionAppearance('class')
            this.playerHand.setSelectionMode(1)
            this.playerHand.image_items_per_row = 14
            dojo.connect(
                this.playerHand,
                'onChangeSelection', this, 'onPlayerHandSelectionChanged'
            )

            // Create cards types:
            // include the whole deck, even though only some are used.
            for (let suit = 1; suit <= 4; suit++) {
                for (let rank = 2; rank <= 14; rank++) {
                    // Build card type id
                    let cardTypeId = this.getCardUniqueId(suit, rank)
                    this.playerHand.addItemType(
                        cardTypeId, // item Id
                        cardTypeId, // sorting "weight"
                        `${g_gamethemeurl}img/cardsnew.png`,
                        cardTypeId  // position in sprite
                    )
                }
            }
            // TODO Add Joker and card backs here.
            let jokerId = this.getCardUniqueId(5, 1)
            this.playerHand.addItemType(
                jokerId, // item Id
                53, // sorting "weight"
                `${g_gamethemeurl}img/cardsnew.png`,
                27  // position in sprite
            )

            // Cards in player's hand
            for (let i in this.gamedatas.hand) {
                let card = this.gamedatas.hand[i]
                let suit = card.type
                let value = card.type_arg
                let uniqueId = this.getCardUniqueId(suit, value)
                this.playerHand.addToStockWithId(uniqueId, card.id)
            }

            // Cards played on table
            for (let i in this.gamedatas.cardsontable) {
                let card = this.gamedatas.cardsontable[i]
                let suit = card.type
                let rank = card.type_arg
                let player_id = card.location_arg
                this.playCardOnTable(player_id, suit, rank, card.id)
            }

            // Get contract

            // Show the trump card (or suit) if it has been chosen
            const turnUpSuit = Number(gamedatas.turnUpSuit)
            const turnUpValue = gamedatas.turnUpValue
            const trumpSuit = Number(gamedatas.trumpSuit)
            this.turnUpSuit = turnUpSuit
            this.showTrumpCard(turnUpSuit, turnUpValue)
            // Show the trump suit if set, otherwise just re show the turn up suit
            this.showTrumpSymbol(trumpSuit ? trumpSuit : turnUpSuit)
            // Sort the card weights to the trump suit if set, otherwise to the turn up suit
            this.updateCardsWeights(trumpSuit ? trumpSuit : turnUpSuit)


            // Create bids TODO: Probably there is a better way to do this...
            // this.bids = gamedatas.bids;

            // // Create suits
            // this.colors = gamedatas.colors;

            // Display contract if it's an actual contract


            // Show icons if necessary
            // this.dealer_id = gamedatas.dealer_id;
            // if (this.dealer_id > 0) {
            //     this.setDealer(this.dealer_id);
            // }

            // this.declarer_id = gamedatas.declarer_id;
            // if (this.declarer_id > 0) {
            //     this.setDeclarer(this.declarer_id);
            // } else {
            //     this.hideDeclarer();
            // }

            // this.accepter_id = gamedatas.accepter_id;
            // if (this.accepter_id > 0) {
            //     this.setPartner(this.accepter_id);
            // } else {
            //     this.hidePartner();
            // }

            // // Update hand counter
            // this.updateHandCounter(gamedatas.handNumber, gamedatas.handsToPlay);


            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into
        //      a new game state. You can use this method to perform some user
        //      interface changes at this moment.
        onEnteringState: function (stateName, args) {
            console.log( 'Entering state: '+stateName );


            if (stateName == 'playerTurn') {
                if (this.isCurrentPlayerActive()) {
                    this.canPlayCard = true;
                    if (args.args._private.possibleCards) {
                        this.updatePossibleCards(args.args._private.possibleCards)
                    }
                }
            }
        },

        // onLeavingState: this method is called each time we are leaving a game
        //          state. You can use this method to perform some user
        //          interface changes at this moment.
        //
        onLeavingState: function ( stateName ) {
            console.log( 'Leaving state: '+stateName );

            switch ( stateName ) {

                /* Example:

                case 'myGameState':

                // Hide the HTML block we are displaying only during this game
                // state
                dojo.style( 'my_html_block_id', 'display', 'none' );

                break;
                */


                case 'dummmy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons"
        // that are displayed in the action status bar (ie: the HTML links in
        // the status bar).
        onUpdateActionButtons: function (stateName, args) {
            console.log(`onUpdateActionButtons: ${stateName}`);

            if ( this.isCurrentPlayerActive() ) {
                switch ( stateName ) {
                    /*
                    Example:

                    case 'myGameState':

                    // Add 3 action buttons in the action status bar:

                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' );
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' );
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' );
                    break;
                    
*/
                case 'playerAcceptTurnUp':
                    this.addActionButton("btn_acceptTurnUp", _("Order Up"), 'onButtonTurnUp', null, false, 'red')
                    this.addActionButton("btn_passTurnUp", _("Pass"), 'onButtonTurnUp', null, false, 'blue')
                    break

                case 'discardTurnUp':
                    this.addActionButton("btn_discard", _("Discard"), 'onButtonDiscard', null, false, 'red')
                    break
                
                case 'jokerChooseSuit':
                    for (var i = 1; i <= 4; i++) {
                        this.addSuitButton(i)
                    }
                    break
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

        Here, you can defines some utility methods that you can use everywhere in your javascript
        script.

        */

        // Get card unique identifier based on its suit and rank
        getCardUniqueId: (suit, rank) => (suit - 1) * 14 + (rank - 2),

        // Get cards selected in hand
        getSelectedCards: function() {
            return this.playerHand.getSelectedItems().map(item => item.id)
        },

        makeAjaxCall: function(methodName, args, onError = error => {}) {
            $('pagemaintitletext').innerHTML = _('Sending move to server...')
            $('generalactions').innerHTML = ''
            args.lock = true
            this.ajaxcall(
                `/${this.game_name}/${this.game_name}/${methodName}.html`,
                args, this, result=>{}, onError)
        },

        // Update cards weights based on current trumpColor
        updateCardsWeights: function (trumpSuit) {
            // shift trump to right
            let suitOrder = [1, 2, 3, 4]
            let rest = suitOrder.splice(trumpSuit)
            suitOrder = [...rest, ...suitOrder]

            // get new ranks for the suit rearrangement
            const weights = {}
            suitOrder.forEach((suit, i) => {
                for (let rank = 7; rank <= 14; rank++) {
                    const weight = (i * 8 + rank)
                    const cardValId = this.getCardUniqueId(suit, rank)
                    weights[cardValId] = weight
                }
            })
            // bowers to the front:
            const jack = 11
            const rightBowerId = this.getCardUniqueId(trumpSuit, jack)
            weights[rightBowerId] = 56
            const sameColor = ((trumpSuit + 2) % 4) || 4
            const leftBowerId =  this.getCardUniqueId(sameColor, jack)
            weights[leftBowerId] = 55
            // best bower/joker
            const bestBowerId = this.getCardUniqueId(5, 1)
            weights[bestBowerId] = 57

            // add Trump Class
            this.playerHand.changeItemsWeight(weights)
            let ranks = [7, 8, 9, 10, 11, 12, 13, 14].map(rank =>
                this.getCardUniqueId(trumpSuit, rank)
            )
            ;[...ranks, leftBowerId, bestBowerId].forEach(cardValId => {
                let cardItem = this.playerHand.getAllItems()
                    .find(c => c.type == cardValId)
                if (cardItem && cardItem.id) {
                    let cardDivId = this.playerHand.getItemDivId(cardItem.id)
                    let cardDiv = document.getElementById(cardDivId)
                    if (cardDiv) {
                        cardDiv.classList.add('stockitem--is-trump')
                    }
                }
            })
        },


        /* Card Management */
        // This function is called any time the selection changes, or if a
        // automatic play can be checked
        // If only one card is selected and it is time to play it, the move is
        // sent to the server, Otherwise, nothing happens
        checkIfPlay: function(noStateCheck) {
            const items = this.playerHand.getSelectedItems()

            if (!this.canPlayCard) return

            if (items.length === 1) {
                const action='playCard'
                if (noStateCheck || this.checkAction(action, true)) {
                    const cardId = items[0].id
                    this.makeAjaxCall(action, { id: cardId })
                }
            }
        },

        playCardOnTable: function(player_id, suit, value, card_id) {
            // Joker
            if (suit == 5) {
                suit = 2
                value = 15
            }
            dojo.place(this.format_block('jstpl_cardontable', {
                x: this.cardwidth * (value - 2),
                y: this.cardheight * (suit - 1),
                player_id
            }), `playertablecard_${player_id}`)

            if (player_id != this.player_id) {
                // Some opponent played a card
                // Move card from player panel
                this.placeOnObject(
                    `cardontable_${player_id}`, `playertable_avatar_${player_id}`
                )
            } else {
                // You played a card. If it exists in your hand, move card from
                // there and remove corresponding item
                if ($(`myhand_item_${card_id}`)) {
                    this.placeOnObject(
                        `cardontable_${player_id}`, `myhand_item_${card_id}`
                    )
                    this.playerHand.removeFromStockById(card_id)
                }
            }
            // In any case: move it to its final destination
            this.slideToObject(
                `cardontable_${player_id}`,
                `playertablecard_${player_id}`
            ).play()
        },

        updatePossibleCards: function (cards) {
            dojo.query('.stockitem--not-possible')
                .removeClass('stockitem--not-possible')
            if (cards === null) {
                return
            }
            dojo.query('.stockitem').forEach(el => {
                const id = el.id.match(/^myhand_item_(\d+)$/)[1]
                const possible = cards.find(card => card.id == id)
                if (!possible) {
                    el.classList.add('stockitem--not-possible')
                }
            })
        },

        addSuitButton : function(suit) {
            var suitName = _(this.suits[suit]['name']);
            this.addActionButton('buttonSuit_' + suit, suitName + ' ' + this.suits[suit]['symbol'], 'onSuitButtonClick', null, false, 'gray');
            var toolTipText = dojo.string.substitute( _("Choose ${s} as the trump suit."), {
                s: suitName
            } );
            this.addTooltipHtml('buttonSuit_' + suit, toolTipText);
        },

        showTrumpCard : function(suit, rank) {
            if (dojo.byId('trumpcardontable') != null) {
                // If trump card already there, remove it to stop cluttering
                dojo.destroy('trumpcardontable')
            }
            if (rank == 15) {
                // If the trump rank is set to card back, make sure it is shown
                suit = 3
            }
            // Joker
            if (suit == 5) {
                suit = 2
                rank = 15
            }
            dojo.place(this.format_block('jstpl_trumpcardontable', {
                x: this.cardwidth * (rank - 2),
                y: this.cardheight * (suit - 1),
            }), 'trumpCard')
            // If trump card is not the placeholder for no trumps, also update
            // the trump symbol
            if (suit > 0 && rank != 15) {
                this.showTrumpSymbol(suit)
            }
        },

        showTrumpSymbol: function(suit) {
            if (dojo.byId('trumpsymbolontable') != null) {
                dojo.destroy('trumpsymbolontable')
            }
            if ([1,2,3,4].includes(suit)) {
                dojo.place(this.format_block('jstpl_trumpsymbolontable', {
                    suit,
                    symbol: this.suits[suit]?.symbol
                }), 'trumpSuit')
            }
        },



        // @Override: client side magic to massage log arguments into
        // displayable localized text
        format_string_recursive: function (log, args) {
            try {
                if (log && args && !args.processed) {
                    args.processed = true

                    // for linking back to the results of the last game
                    if (args.seeResult !== undefined) {
                        // HACK: Notification handler needs this
                        args.copyOfResult = args.seeResult
                        args.seeResult = this.linkToResult(args.n, args.seeResult)
                    }

                    // Format cards
                    if (args.card !== undefined) {
                        let name, colour, symbol
                        const { type: suit, type_arg: rank } = args.card

                        // joker
                        if (suit == 5) {
                            name = this.ranks?.[15]
                            colour = 'blue'
                        } else {
                            name = this.ranks?.[rank] + this.suits[suit]?.symbol
                            colour = (suit == 1 || suit == 3) ? 'black' : 'red'
                        }
                        args.card_name = dojo.string.substitute(
                            '<strong style="color:${colour};">${name}</strong>',
                            { colour, name }
                        )
                    }

                    // if (args.display_suit !== undefined) {
                    //     let display_suit = args.display_suit
                    //     let name, colour, symbol
                    //     name = this.suits[display_suit].name
                    //     symbol = this.suits[display_suit].symbol
                    //     colour = (display_suit == 1 || display_suit == 3) ? 'red' : 'black';

                    //     args.display_suit = dojo.string.substitute('${name} <strong style="color:${colour};">${symbol}</strong>', { symbol, colour, name });
                    // }

                }
            } catch (e) {
                console.error(
                    'Exception while formatting "%o" with "%o":\n%o',
                    log, args, e
                )
            }
            return this.inherited(arguments);
        },

        ///////////////////////////////////////////////////
        //// Player's action

        onPlayerHandSelectionChanged: function () {
            this.checkIfPlay(false)
        },

        onButtonTurnUp : function (event) {
            if (this.checkAction('acceptOrPass')) {
                const button_id = dojo.getAttr(event.currentTarget, 'id')
                const acceptOrPass = button_id.split('_')[1]
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + 'acceptOrPass' + ".html", {
                    bid: acceptOrPass,
                    lock : true
                }, this, function(result) {
                }, function(is_error) {
                });
            }
        },

        onButtonDiscard : function (event) {
            if (this.checkAction('discard')) {
                const selected = this.getSelectedCards()

                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + 'discard' + ".html", {
                    cards: selected.join(','),
                    lock : true
                }, this, function(result) {
                }, function(is_error) {
                });
            }
        },

        onSuitButtonClick : function (event) {
            if (this.checkAction('chooseTrump')) {
                var button_id = dojo.getAttr(event.currentTarget, 'id');
                var bid_id = button_id.split('_')[1];
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + 'chooseTrump' + ".html", {
                    color: bid_id,
                    lock : true
                }, this, function(result) {
                }, function(is_error) {
                });
            }
        },

        /* Example:

        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/euchrenisterius/euchrenisterius/myAction.html", {
                lock: true,
                myArgument1: arg1,
                myArgument2: arg2,
                ...
            },
            this, function( result ) {

                // What to do after the server call if it succeeded
                // (most of the time: nothing)

            }, function( is_error) {

                // What to do after the server call in anyway (success or failure)
                // (most of the time: nothing)

            } );
        },

        */


        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
        setupNotifications:

        In this method, you associate each of your game notifications with your local method to handle it.

        Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
        your euchrenisterius.game.php file.

        */
        setupNotifications: function() {
            console.log( 'notifications subscriptions setup' )

            dojo.subscribe('newDeal', this, 'notifyNewDeal')
            dojo.subscribe('newHand', this, 'notifyNewHand')
            dojo.subscribe('pickUpCard', this, 'notifyPickUpCard')
            dojo.subscribe('discarded', this, 'notifyDiscarded')
            dojo.subscribe('allPassed', this, 'notifyAllPassed')
            dojo.subscribe('chooseTrump', this, 'notifyChooseTrump')
            dojo.subscribe('playCard', this, 'notifyPlayCard')
            dojo.subscribe('trickWin', this, 'notifyTrickWin')
            this.notifqueue.setSynchronous('trickWin', 1000)
            dojo.subscribe('giveAllCardsToPlayer', this, "notifyGiveAllCardsToPlayer");


        },

        // From this point and below, you can write your game
        // notifications handling methods

        notifyNewDeal: function (notif) {
            console.log(notif)
            // this.updateHandCounter(notif.args.current_hand,
            //     notif.args.hands_to_play);
            // for ( let player_id in this.gamedatas.players) {
            //     this.updatePlayerTrickCount(player_id, 0)
            // }
            // this.dealer = notif.args.dealer_id

            // activate all players, inactive inactive player
        },

        notifyNewHand: function (notif) {
            this.playerHand.removeAll()
            notif?.args?.cards?.forEach(card => {
                let { type: suit, type_arg: value } = card
                let cardId = this.getCardUniqueId(suit, value)
                this.playerHand.addToStockWithId(cardId, card.id)
            })
        },

        notifyPickUpCard : function(notif) {
            // We received some new cards that we need to add to the hand.

            for ( var i in notif.args.cards) {
                let card = notif.args.cards[i]
                let color = card.type
                let value = card.type_arg
                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id)
            }

            this.updateCardsWeights(notif.args.trumpSuit)
        },

        notifyDiscarded : function(notif) {
            // We discarded a card.

            for ( var i in notif.args.cards) {
                let card = notif.args.cards[i]
                this.playerHand.removeFromStockById(card)
            }
        },

        notifyAllPassed : function(notif) {
            this.showTrumpCard(0,15); //TESTING
            this.showTrumpSymbol(0);
        },

        notifyChooseTrump : function(notif) {
            this.showTrumpSymbol(notif.args.trumpSuit)

            this.updateCardsWeights(notif.args.trumpSuit)
        },

        notifyPlayCard: function (notif) {
            // play card on the table
            const {
                type: suit,
                type_arg: value,
                id
            } = notif?.args?.card
            this.playCardOnTable(notif.args.player_id, suit, value, id)
        },

        notifyTrickWin: function (notif) {
            // this.updatePlayerTrickCount(notif.args.player_id,
            //     notif.args.trick_won)

            // $('trick_count_wrap').innerHTML =
            //     dojo.string.substitute( _('Trick ${n} of 25'), {
            //         n: Math.min(Number(notif.args.next) + 1, 25)
            //     })

            // BELOTE COINCHE: clear the old tricks from logs.
            // var me = this
            // setTimeout(function() {
            // 	me.giveAllCardsToPlayer(notif.args.player_id).then(function() {
            // 		me.clearOldTricksLogs(notif.args.trick_count_value - 1)
            // 		me.updatePlayerTrickCount(notif.args.player_id, notif.args.trick_won)
            // 	})
            // }, 1500)
        },

        notifyGiveAllCardsToPlayer: function (notif) {
            // Move all cards on table to given table, then destroy them
            const winnerId = notif.args.player_id;
            for (let player_id in this.gamedatas.players) {
                const anim = this.slideToObject(
                    `cardontable_${player_id}`, `playertable_avatar_${winnerId}`)
                    dojo.connect(anim, 'onEnd',
                    node => this.fadeOutAndDestroy(node, 500)
                )
                anim.play()
            }
        },

    })
})
