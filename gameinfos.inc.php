<?php
$gameinfos = [
    'game_name' => "euchrenisterius",
    'designer' => '',
    'artist' => '',
    'year' => 1848,
    'publisher' => '(Public Domain)',
    'publisher_website' => '',
    'publisher_bgg_id' => 171,
    'bgg_id' => 6901,
    'players' => [4],
    'suggest_player_number' => null,
    'not_recommend_player_number' => null,

    // Estimated game duration, in minutes (used only for the launch, afterward
    // the real duration is computed)
    'estimated_duration' => 30,

    // Time in second add to a player when "giveExtraTime" is called (speed 
    // profile = fast)
    'fast_additional_time' => 30,
    'medium_additional_time' => 40,
    'slow_additional_time' => 50,

    // If you are using a tie breaker in your game (using "player_score_aux"),
    // you must describe here  the formula used to compute "player_score_aux". 
    // This description will be used as a tooltip to explain the tie breaker to
    // the players.
    // Note: if you are NOT using any tie breaker, leave the empty string.
    //
    // Example: 'tie_breaker_description' => totranslate( "Number of remaining
    // cards in hand" ),
    'tie_breaker_description' => "",

    // If in the game, all losers are equal (no score to rank them or explicit 
    // in the rules that losers are not ranked between them), set this to true
    // The game end result will display "Winner" for the 1st player and "Loser" 
    // for all other players
    'losers_not_ranked' => true,


    'solo_mode_ranked' => false,
    'is_beta' => 1,
    'is_coop' => 0,
    'language_dependency' => false,
    'complexity' => 2,
    'luck' => 3,
    'strategy' => 3,
    'diplomacy' => 3,
    'player_colors' => ["ff0000", "008000", "0000ff", "ffa500", "773300"],

    // Favorite colors support : if set to "true", support attribution of 
    // favorite colors based on player's preferences (see 
    // reattributeColorsBasedOnPreferences PHP method)
    // NB: this parameter is used only to flag games supporting this feature; 
    // you must use (or not use) reattributeColorsBasedOnPreferences PHP method 
    // to actually enable or disable the feature.
    'favorite_colors_support' => true,

    // When doing a rematch, the player order is swapped using a "rotation" so
    // the starting player is not the same
    // If you want to disable this, set this to true
    'disable_player_order_swap_on_rematch' => false,

    // Game interface width range (pixels)
    // Note: game interface = space on the left side, without the column on the right
    'game_interface_width' => [

        // Minimum width
        //  default: 740
        //  maximum possible value: 740 (ie: your game interface should fit with
        //  a 740px width (correspond to a 1024px screen) minimum possible 
        // value: 320 (the lowest value you specify, the better the display is 
        // on mobile)
        'min' => 740,

        // Maximum width
        //  default: null (ie: no limit, the game interface is as big as the 
        // player's screen allows it).
        //  maximum possible value: unlimited
        //  minimum possible value: 740
        'max' => null
    ],

    // Game presentation
    // Short game presentation text that will appear on the game description 
    // page, structured as an array of paragraphs. Each paragraph must be 
    // wrapped with totranslate() for translation and should not contain html 
    // (plain text without formatting). A good length for this text is between
    // 100 and 150 words (about 6 to 9 lines on a standard display)
    'presentation' => [
       totranslate("euchrenisterius is a trick-taking card game commonly played in Australia, Canada, New Zealand, Great Britain, and the United States. It is played with a deck of 24, 28, or 32 standard playing cards. Normally there are four players, two on each team, although there are variations that range from two to nine players."),
       totranslate("euchrenisterius was responsible for introducing the joker into the modern deck of cards. The Joker Deck was introduced to Americanized euchrenisterius around 1860 with the joker acting as a top trump or best Bower (from the German word Bauer, “farmer”, denoting also the Jack – see Bester Bube). euchrenisterius is believed to be closely related to the French game Écarté, the seventeenth-century game Loo, and the 19th-century game Juckerspiel. - Wikipedia"),
    ],

    // Games categories
    //  You can attribute a maximum of FIVE "tags" for your game.
    //  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
    //  Please see the "Game meta information" entry in the BGA Studio documentation for a full list of available tags:
    //  http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
    //  IMPORTANT: this list should be ORDERED, with the most important tag first.
    //  IMPORTANT: it is mandatory that the FIRST tag is 1, 2, 3 and 4 (= game category)
    'tags' => [1, 10, 23, 200],


    //////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)

    // simple : A plays, B plays, C plays, A plays, B plays, ...
    // circuit : A plays and choose the next player C, C plays and choose the next player D, ...
    // complex : A+B+C plays and says that the next player is A+B
    'is_sandbox' => false,
    'turnControl' => 'simple'
];
