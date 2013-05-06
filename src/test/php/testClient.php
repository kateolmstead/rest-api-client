<?php

require_once '../../main/php/PlaynomicsApiClient.php';
require_once '../../main/php/TransactionCurrency.php';
require_once '../../main/php/TransactionCurrencyCode.php';
require_once '../../main/php/TransactionType.php';

function main() {
    date_default_timezone_set('UTC');

    $json = file_get_contents("../resources/config.json");
    $config = json_decode($json);

    $app_id = $config->app_id;
    $api_key = $config->api_key;
    $player_one_id = 1;

    $player_two_email = "player2@gmail.com";
    //set up the proxy to send requests through Charles Proxy
    $proxy = "localhost:8888";
    $api_client = new PlaynomicsApiClient($app_id, $api_key, $proxy);
   
    $api_client->test_mode = true;

    //start a session
    $session_id = 10;

    $args = array(
        "user_id" => $player_one_id,
        "session_id" => $session_id,
        "site" => "http://awesomegames.com"
    );
    echo "sessionStart with args : ". var_dump($args);
    $api_client->sessionStart($args);

    //start a game
    $instance_id = 100;

    $args = array(
        "user_id" => $player_one_id,
        "session_id" => $session_id,
        "instance_id" => $instance_id,
        "site" => "http://awesomegames.com/mmorpg",
        "type" => "mmorpg",
        "game_id" => "game_id"
    );

    echo "gameStart with args : ".var_dump($args);
    $api_client->gameStart($args);
    
    //player1 purchase in game currency

    $transaction_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "transaction_id" => $transaction_id,
        "currencies" => array(
            0 => TransactionCurrency::createVirtual("coins", 50),
            1 => TransactionCurrency::createReal("USD", -1)
        ), 
        "type" => TransactionType::CurrencyConvert
    );

    echo "CurrencyConvert transaction with args : " . var_dump($args);
    $api_client->transaction($args);
    
    //player1 purchase a game item with real currency
    $transaction_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "transaction_id" => $transaction_id,
        "currencies" => array(
            1 => TransactionCurrency::createReal("USD", 1)
        ), 
        "type" => TransactionType::BuyItem,
        "quantity" => 1,
        "item_id" => "Sword"
    );

    echo "BuyItem transaction with args : " . var_dump($args);
    $api_client->transaction($args);
    
    //player1 purchase a game item with virtual currency
    $transaction_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "transaction_id" => $transaction_id,
        "currencies" => array(
            1 => TransactionCurrency::createVirtual("coins", 10)
        ), 
        "type" => TransactionType::BuyItem,
        "quantity" => 1,
        "item_id" => "Sword"
    );

    echo "BuyItem transaction with args : " . var_dump($args);
    $api_client->transaction($args);
    
    //player1 completes milestone CUSTOM1
    $args = array(
        "user_id" => $player_one_id,
        "milestone_name" => "CUSTOM1",
        "milestone_id" => rand(1, 100)
    );

    echo "milestone with args : " . var_dump($args);
    $api_client->milestone($args);

    //player1 invites player2 to join the game
    $invitation_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "invitation_id" => $invitation_id,
        "recipient_address" => hash_hmac("sha256", $player_two_email, "INVITATION_KEY")
    );

    echo "invitationSent with args : " . var_dump($args);
    $api_client->invitationSent($args);
    //end the game
    $args = array(
        "user_id" => $player_one_id, 
        "session_id" => $session_id,
        "instance_id" => $instance_id,
        "reason" => "quit"
    );
    $api_client->gameEnd($args);
    echo "gameEnd with args : " . var_dump($args);
    //end session
    $args = array(
        "user_id" => $player_one_id, 
        "session_id" => $session_id,
        "reason" => "quit"
    );

    $api_client->sessionEnd($args);
    echo "sessionEnd with args : " . var_dump($args);
    //player two joins this game from the invitation that 
    $player_two_id = 2;
    //start another session 
    $session_id ++;

    $args = array(
        "user_id" => $player_two_id,
        "session_id" => $session_id,
        "site" => "http://awesomegames.com"
    );
    echo "sessionStart with args : " . var_dump($args);
    $api_client->sessionStart($args);

    //report the demographic info that player2 fills out when he joins

    $args = array(
        "user_id" => $player_two_id,
        "country" => "USA",
        "subdivision" => "94131",
        "sex" => "F",
        "birth_year" => 1980,
        "source" => "invitation",
        "source_user" => $player_one_id
    );

    //start another game 
    $instance_id ++;

    $args = array(
        "user_id" => $player_two_id,
        "session_id" => $session_id,
        "instance_id" => $instance_id,
        "site" => "http://awesomegames.com/mmorpg",
        "type" => "mmorpg",
        "game_id" => 1
    );

    echo "gameStart with args : " . var_dump($args);
    $api_client->gameStart($args);
    
    //player2 accepts player1's invitations
    $args = array(
        "user_id" => $player_two_id,
        "recipient_user_id" => $player_two_id,
        "invitation_id" => $invitation_id
    );

    $api_client->invitationResponse($args);
    echo "invitationResponse with args : " . var_dump($args);
    //end the game
    $args = array(
        "user_id" => $player_two_id, 
        "session_id" => $session_id,
        "instance_id" => $instance_id,
        "reason" => "quit"
    );
    $api_client->gameEnd($args);
    echo "gameEnd with args : " . var_dump($args);
    //end session
    $args = array(
        "user_id" => $player_two_id, 
        "session_id" => $session_id,
        "reason" => "quit"
    );
    $api_client->sessionEnd($args);
    echo "sessionEnd with args : " . var_dump($args);
}
main();
?>