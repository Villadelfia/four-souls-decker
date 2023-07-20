<?php
// Output as plain text.
header("Content-Type: text/plain");

// Get the card db.
$card_db = file_get_contents("cards.json");
$card_db = json_decode($card_db, true);

// Load in the cards.
$all_cards = array();
$character = array();
$starting_item = array();
$soul = array();
$room = array();
$loot = array();
$treasure = array();
$monster = array();

foreach($card_db["character"] as $c) {
    $c["type"] = "character";
    $all_cards[$c["id"]] = $c;
    foreach(range(1, $c["count"]) as $i) $character[$c["id"] . $i] = $c;
}

foreach($card_db["starting_item"] as $c) {
    $c["type"] = "starting item";
    $all_cards[$c["id"]] = $c;
    foreach(range(1, $c["count"]) as $i) $starting_item[$c["id"] . $i] = $c;
}

foreach($card_db["soul"] as $c) {
    $c["type"] = "soul";
    $all_cards[$c["id"]] = $c;
    foreach(range(1, $c["count"]) as $i) $soul[$c["id"] . $i] = $c;
}

foreach($card_db["room"] as $c) {
    $c["type"] = "room";
    $all_cards[$c["id"]] = $c;
    foreach(range(1, $c["count"]) as $i) $room[$c["id"] . $i] = $c;
}

foreach($card_db["loot"] as $c) {
    $c["type"] = "loot";
    $all_cards[$c["id"]] = $c;
    foreach(range(1, $c["count"]) as $i) $loot[$c["id"] . $i] = $c;
}

foreach($card_db["treasure"] as $c) {
    $c["type"] = "treasure";
    $all_cards[$c["id"]] = $c;
    foreach(range(1, $c["count"]) as $i) $treasure[$c["id"] . $i] = $c;
}

foreach($card_db["monster"] as $c) {
    $c["type"] = "monster";
    $all_cards[$c["id"]] = $c;
    foreach(range(1, $c["count"]) as $i) $monster[$c["id"] . $i] = $c;
}

// Initialize decks to generate.
$character_options = array(
    "player_1" => array("slots_left" => 3, "cards" => array()),
    "player_2" => array("slots_left" => 3, "cards" => array()),
    "player_3" => array("slots_left" => 3, "cards" => array()),
    "player_4" => array("slots_left" => 3, "cards" => array())
);
$soul_deck = array("slots_left" => 3, "cards" => array());
$loot_deck = array("slots_left" => 100, "cards" => array());
$treasure_deck = array("slots_left" => 100, "cards" => array());
$monster_deck = array("slots_left" => 100, "cards" => array());

foreach($card_db["deck_ratios"]["loot"] as $c) {
    $loot_deck["cards"][$c["tag"]] = array("slots_left" => $c["count"], "total_cards" => 0, "cards" => array());
}

foreach($card_db["deck_ratios"]["treasure"] as $c) {
    $treasure_deck["cards"][$c["tag"]] = array("slots_left" => $c["count"], "total_cards" => 0, "cards" => array());
}

foreach($card_db["deck_ratios"]["monster"] as $c) {
    $monster_deck["cards"][$c["tag"]] = array("slots_left" => $c["count"], "total_cards" => 0, "cards" => array());
}

// Initialize randomness.
// Randomly seed.
$rng = new \Random\Randomizer();
$seed = $rng->getInt(0, 4_294_967_295);

// If a seed was passed, use that instead.
if(isset($_GET["seed"])) {
    $seed = $_GET["seed"];
    if((string)(int)$seed === $seed) $seed = (int)$seed;
    else                             $seed = abs(crc32($seed)) % 4_294_967_295;
}

// Shuffle the decks.
$rng = new \Random\Randomizer(new \Random\Engine\Xoshiro256StarStar($seed));
$character = $rng->shuffleArray($character);
$rng = new \Random\Randomizer(new \Random\Engine\Xoshiro256StarStar($seed));
$soul = $rng->shuffleArray($soul);
$rng = new \Random\Randomizer(new \Random\Engine\Xoshiro256StarStar($seed));
$loot = $rng->shuffleArray($loot);
$rng = new \Random\Randomizer(new \Random\Engine\Xoshiro256StarStar($seed));
$treasure = $rng->shuffleArray($treasure);
$rng = new \Random\Randomizer(new \Random\Engine\Xoshiro256StarStar($seed));
$monster = $rng->shuffleArray($monster);


// Build the character deck.
foreach(range(1, 4) as $v) {
    $character_options["player_" . $v] = array(
        "slots_left" => 0,
        "cards" => array(
            array("main" =>array_shift($character)),
            array("main" =>array_shift($character)),
            array("main" =>array_shift($character))
        )
    );
}

// Go over all the drawn cards for the characters, only for the first two players.
foreach(range(1, 2) as $v) {
    foreach($character_options["player_" . $v]["cards"] as $k => $c) {
        // If they happen to be a 3+ card, also draw an alt card for two players.
        if($c["main"]["threeplus"]) {
            $new_draw = array_shift($character);
            while($new_draw["threeplus"]) $new_draw = array_shift($character);
            $character_options["player_" . $v]["cards"][$k]["alt"] = $new_draw;
        }
    }
}

// Some info.
echo "=== Binding of Isaac: Four Souls Deck Generator ===\n";
echo "This page will generate all the decks needed to play a game of Four Souls\n";
echo "All the cards, and only those, that are in the Retail Requiem Ultimate Collector's Box are included here.\n";
echo "The seed for these decks is \"" . $seed . "\". A seed can be specified by appending ?seed=... to the url.\n";
echo "To get this exact setup again, go to https://www.villadelfia.org/deckme/?seed=" . $seed . ".\n\n\n";

// Output the chosen character decks.
echo "=== Character Choices ===\n";
echo "Choose from one of the given three character options.\n\n";

foreach(range(1, 4) as $p) {
    echo "Player " . $p . ":\n";
    foreach($character_options["player_" . $p]["cards"] as $c) {
        if(isset($c["alt"])) {
            echo "- 1-2P \"" . $c["alt"]["name"] . "\" starting with \"" . $all_cards[$c["alt"]["link"][0]]["name"] . "\".\n";
            echo "  3-4P \"" . $c["main"]["name"] . "\" (👥) starting with \"" . $all_cards[$c["main"]["link"][0]]["name"] . "\".\n";
        } else {
            echo "- \"" . $c["main"]["name"] . "\" starting with \"" . $all_cards[$c["main"]["link"][0]]["name"] . "\".\n";
        }
    }

    if($p < 4) echo "\n";
}


// Build the soul deck.
$soul_deck = array("slots_left" => 0, "cards" => array(
    array_shift($soul),
    array_shift($soul),
    array_shift($soul)
));

// Output the soul deck.
echo "\n\n=== Bonus Souls ===\n";
echo "Use the following three bonus souls:\n\n";

foreach($soul_deck["cards"] as $c) {
    echo "- " . $c["name"] . "\n";
}


// Build the loot deck.
// There are, as of now, no loot cards that are limited to 3 or more players.
while($loot_deck["slots_left"] > 0) {
    $draw = array_shift($loot);
    foreach($draw["tags"] as $t) {
        if($loot_deck["cards"][$t]["slots_left"] > 0) {
            $loot_deck["slots_left"] -= 1;
            $loot_deck["cards"][$t]["slots_left"] -= 1;
            $loot_deck["cards"][$t]["total_cards"] += 1;
            if(isset($loot_deck["cards"][$t]["cards"][$draw["id"]])) {
                $loot_deck["cards"][$t]["cards"][$draw["id"]] += 1;
            } else {
                $loot_deck["cards"][$t]["cards"][$draw["id"]] = 1;
            }
            break;
        }
    }
}

// Output the loot deck.
echo "\n\n=== Loot Deck ===\n";
echo "Use the following 100 card loot deck:\n";

foreach($loot_deck["cards"] as $k => $v) {
    if($k == "soul") echo "\nSouls (" . $v["total_cards"] . "):\n";
    if($k == "trinket") echo "\nTrinkets (" . $v["total_cards"] . "):\n";
    if($k == "butter_bean") echo "\nButter Beans (" . $v["total_cards"] . "):\n";
    if($k == "shard_heart") echo "\nHearts and Dice Shards (" . $v["total_cards"] . "):\n";
    if($k == "pill_rune") echo "\nPills and Runes (" . $v["total_cards"] . "):\n";
    if($k == "battery") echo "\nBatteries (" . $v["total_cards"] . "):\n";
    if($k == "bomb") echo "\nBombs (" . $v["total_cards"] . "):\n";
    if($k == "nickel_dime") echo "\nNickels and Dimes (" . $v["total_cards"] . "):\n";
    if($k == "1cent") echo "\nPennies (" . $v["total_cards"] . "):\n";
    if($k == "2cent") echo "\n2 Cents (" . $v["total_cards"] . "):\n";
    if($k == "3cent") echo "\n3 Cents (" . $v["total_cards"] . "):\n";
    if($k == "4cent") echo "\n4 Cents (" . $v["total_cards"] . "):\n";
    if($k == "other") echo "\nOther Loot (" . $v["total_cards"] . "):\n";
    foreach($v["cards"] as $id => $ct) {
        echo "- " . $ct . "x " . $all_cards[$id]["name"] . " (";
        foreach($all_cards[$id]["tags"] as $t) {
            if($t == "soul") echo "👻";
            if($t == "trinket") echo "🔮";
            if($t == "butter_bean") echo "⟲";
            if($t == "shard_heart") echo "💙";
            if($t == "pill_rune") echo "💊";
            if($t == "battery") echo "🔋";
            if($t == "bomb") echo "💣";
            if($t == "nickel_dime") echo "⓾";
            if($t == "1cent") echo "❶";
            if($t == "2cent") echo "❷";
            if($t == "3cent") echo "❸";
            if($t == "4cent") echo "❹";
            if($t == "other") echo "❔";
        }
        echo ")\n";
    }
}


// Build the treasure deck.
// If we need to reroll some of these, we'll do so when showing the deck.
while($treasure_deck["slots_left"] > 0) {
    $draw = array_shift($treasure);
    $slots_before = $treasure_deck["slots_left"];
    foreach($draw["tags"] as $t) {
        if($treasure_deck["cards"][$t]["slots_left"] > 0) {
            $treasure_deck["slots_left"] -= 1;
            $treasure_deck["cards"][$t]["slots_left"] -= 1;
            $treasure_deck["cards"][$t]["total_cards"] += 1;
            if(isset($treasure_deck["cards"][$t]["cards"][$draw["id"]])) {
                $treasure_deck["cards"][$t]["cards"][$draw["id"]] += 1;
            } else {
                $treasure_deck["cards"][$t]["cards"][$draw["id"]] = 1;
            }
            break;
        }
    }

    // Push it back onto the end if it couldn't be placed.
    if($slots_before == $treasure_deck["slots_left"]) {
        array_push($treasure, $draw);
    }
}

// Output the treasure deck.
echo "\n\n=== Treasure Deck ===\n";
echo "Use the following 100 card treasure deck:\n";

foreach($treasure_deck["cards"] as $k => $v) {
    if($k == "soul") echo "\nSoul Treasure (" . $v["total_cards"] . "):\n";
    if($k == "oneuse") echo "\nSingle Use Treasure (" . $v["total_cards"] . "):\n";
    if($k == "paid") echo "\nPaid Treasure (" . $v["total_cards"] . "):\n";
    if($k == "active") echo "\nActivated Treasure (" . $v["total_cards"] . "):\n";
    if($k == "passive") echo "\nPassive Treasure (" . $v["total_cards"] . "):\n";
    foreach($v["cards"] as $id => $ct) {
        if($all_cards[$id]["threeplus"]) {
            echo "- 1-2P ";
            foreach($treasure as $newk => $newv) {
                $is_threeplus = $newv["threeplus"];
                $has_tag = array_search($k, $newv["tags"]);
                if($is_threeplus === false && $has_tag !== false) {
                    echo $newv["name"] . " (";
                    foreach($newv["tags"] as $t) {
                        if($t == "soul") echo "👻";
                        if($t == "oneuse") echo "🔂";
                        if($t == "paid") echo "💲";
                        if($t == "active") echo "↷";
                        if($t == "passive") echo "💰";
                    }
                    echo ")";
                    $treasure[$newk]["threeplus"] = true;
                    break;
                }
            }
            echo "\n";
            echo "  3-4P " . $all_cards[$id]["name"] . " (👥";
            foreach($all_cards[$id]["tags"] as $t) {
                if($t == "soul") echo "👻";
                if($t == "oneuse") echo "🔂";
                if($t == "paid") echo "💲";
                if($t == "active") echo "↷";
                if($t == "passive") echo "💰";
            }
            echo ")\n";
        } else {
            echo "- " . $all_cards[$id]["name"] . " (";
            foreach($all_cards[$id]["tags"] as $t) {
                if($t == "soul") echo "👻";
                if($t == "oneuse") echo "🔂";
                if($t == "paid") echo "💲";
                if($t == "active") echo "↷";
                if($t == "passive") echo "💰";
            }
            echo ")\n";
        }
    }
}


// Build the monster deck.
// If we need to reroll some of these, we'll do so when showing the deck.
while($monster_deck["slots_left"] > 0) {
    $draw = array_shift($monster);
    $slots_before = $monster_deck["slots_left"];
    foreach($draw["tags"] as $t) {
        if($monster_deck["cards"][$t]["slots_left"] > 0) {
            $monster_deck["slots_left"] -= 1;
            $monster_deck["cards"][$t]["slots_left"] -= 1;
            $monster_deck["cards"][$t]["total_cards"] += 1;
            if(isset($monster_deck["cards"][$t]["cards"][$draw["id"]])) {
                $monster_deck["cards"][$t]["cards"][$draw["id"]] += 1;
            } else {
                $monster_deck["cards"][$t]["cards"][$draw["id"]] = 1;
            }

            // Check for linked cards.
            if(count($draw["link"]) > 0 && // There is a linked card.
               isset($all_cards[$draw["link"][0]]) && // And it's a card id.
               !isset($monster_deck["cards"][$t]["cards"][$all_cards[$draw["link"][0]]["id"]]) // And it's not in the deck yet.
            ) {
                $linked = $all_cards[$draw["link"][0]];
                $monster_deck["slots_left"] -= 1;
                $monster_deck["cards"][$t]["slots_left"] -= 1;
                $monster_deck["cards"][$t]["total_cards"] += 1;
                $monster_deck["cards"][$t]["cards"][$linked["id"]] = 1;
            }

            break;
        }
    }

    // Push it back onto the end if it couldn't be placed.
    if($slots_before == $monster_deck["slots_left"]) {
        array_push($monster, $draw);
    }
}

// Output the monster deck.
echo "\n\n=== Monster Deck ===\n";
echo "Use the following 100 card monster deck:\n";

foreach($monster_deck["cards"] as $k => $v) {
    if($k == "epic_boss") echo "\nEpic Boss Monster (" . $v["total_cards"] . "):\n";
    if($k == "normal_boss") echo "\nBoss Monster (" . $v["total_cards"] . "):\n";
    if($k == "holy_charmed") echo "\nHoly/Charmed Monster (" . $v["total_cards"] . "):\n";
    if($k == "cursed") echo "\nCursed Monster (" . $v["total_cards"] . "):\n";
    if($k == "basic") echo "\nBasic Monster (" . $v["total_cards"] . "):\n";
    if($k == "bad_event") echo "\nNegative Event (" . $v["total_cards"] . "):\n";
    if($k == "good_event") echo "\nPositive Event (" . $v["total_cards"] . "):\n";
    if($k == "curse") echo "\nCurse (" . $v["total_cards"] . "):\n";
    foreach($v["cards"] as $id => $ct) {
        if($all_cards[$id]["threeplus"]) {
            echo "- 1-2P ";
            foreach($monster as $newk => $newv) {
                $is_threeplus = $newv["threeplus"];
                $has_tag = array_search($k, $newv["tags"]);
                if($is_threeplus === false && $has_tag !== false) {
                    echo $newv["name"] . " (";
                    foreach($newv["tags"] as $t) {
                        if($t == "epic_boss") echo "☠";
                        if($t == "normal_boss") echo "💀";
                        if($t == "holy_charmed") echo "😇";
                        if($t == "cursed") echo "😈";
                        if($t == "basic") echo "🧟";
                        if($t == "bad_event") echo "💩";
                        if($t == "good_event") echo "😃";
                        if($t == "curse") echo "🤬";
                        if($t == "house_ruled") echo "🏠";
                    }
                    echo ")";
                    $treasure[$newk]["threeplus"] = true;
                    break;
                }
            }
            echo "\n";
            echo "  3-4P " . $all_cards[$id]["name"] . " (👥";
            foreach($all_cards[$id]["tags"] as $t) {
                if($t == "epic_boss") echo "☠";
                if($t == "normal_boss") echo "💀";
                if($t == "holy_charmed") echo "😇";
                if($t == "cursed") echo "😈";
                if($t == "basic") echo "🧟";
                if($t == "bad_event") echo "💩";
                if($t == "good_event") echo "😃";
                if($t == "curse") echo "🤬";
                if($t == "house_ruled") echo "🏠";
            }
            echo ")\n";
        } else {
            echo "- " . $all_cards[$id]["name"] . " (";
            foreach($all_cards[$id]["tags"] as $t) {
                if($t == "epic_boss") echo "☠";
                if($t == "normal_boss") echo "💀";
                if($t == "holy_charmed") echo "😇";
                if($t == "cursed") echo "😈";
                if($t == "basic") echo "🧟";
                if($t == "bad_event") echo "💩";
                if($t == "good_event") echo "😃";
                if($t == "curse") echo "🤬";
                if($t == "house_ruled") echo "🏠";
            }
            if(count($all_cards[$id]["link"]) > 0 && !isset($all_cards[$all_cards[$id]["link"][0]])) {
                echo "; " . $all_cards[$id]["link"][0];
            }
            echo ")\n";
        }
    }
}

echo "\n\n=== Info ===\n";
echo "This tool was created by Hana Nova, ©2023.\n";
echo "The data behind the tool can be found at https://www.villadelfia.org/deckme/cards.json.";
echo "Find the code on github at https://github.com/Villadelfia/four-souls-decker."
?>
