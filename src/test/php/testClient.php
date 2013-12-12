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

    //set up the proxy to send requests through Charles Proxy
    //$proxy = "localhost:8888";
    $api_client = new PlaynomicsApiClient($app_id, $api_key, $proxy);
    //don't log these events to your control panel dashboard
    $api_client->test_mode = true;

    $args = array(
        "user_id" => $player_one_id
    );
    echo "userInfo with args : ";
    var_dump($args);
    $api_client->appStart($args);

    //report the demographic info that player1 fills out when she joins
    $args = array(
        "user_id" => $player_one_id,
        "sex" => "F",
        "birth_year" => 1980,
        "source" => "invitation",
        "source_user" => $player_one_id,
        "source_campaign" => "UserReferral",
        "install_time" => time()
    );
    echo "userInfo with args : ";
    var_dump($args);
    $api_client->userInfo($args);

    //player1 purchase in game currency

    $transaction_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "transaction_id" => $transaction_id,
        "currencies" => array(
            0 => TransactionCurrency::createVirtual("Gold Coins", 500),
            1 => TransactionCurrency::createReal("USD", -10)
        ), 
        "type" => TransactionType::CurrencyConvert
    );

    echo "CurrencyConvert transaction with args : ";
    var_dump($args);
    $api_client->transaction($args);
    
    //player1 purchase a game item with real currency
    $transaction_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "transaction_id" => $transaction_id,
        "currencies" => array(
            0 => TransactionCurrency::createReal("USD", .99)
        ), 
        "type" => TransactionType::BuyItem,
        "quantity" => 1,
        "item_id" => "Sword"
    );

    echo "BuyItem transaction with args : ";
    var_dump($args);
    $api_client->transaction($args);
    
    //player1 purchases attention currency with premium currency 
    $transaction_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "transaction_id" => $transaction_id,
        "currencies" => array(
            0 => TransactionCurrency::createVirtual("Gold Coins", -10),
            1 => TransactionCurrency::createVirtual("Energy", 100),
        ), 
        "type" => TransactionType::CurrencyConvert,
    );

    echo "CurrencyConvert transaction with args : ";
    var_dump($args);
    $api_client->transaction($args);

    //player1 purchases a game item with premium currency
    $transaction_id = rand(1, 100);
    $args = array(
        "user_id" => $player_one_id,
        "transaction_id" => $transaction_id,
        "currencies" => array(
            1 => TransactionCurrency::createVirtual("Gold Coins", 5)
        ), 
        "type" => TransactionType::BuyItem,
        "quantity" => 20,
        "item_id" => "Light Armor"
    );

    echo "BuyItem transaction with args : ";
    var_dump($args);
    $api_client->transaction($args);
    
    //player1 completes milestone CUSTOM1
    $args = array(
        "user_id" => $player_one_id,
        "milestone_name" => "CUSTOM1",
        "milestone_id" => rand(1, 1000000)
    );

    echo "milestone with args : ";
    var_dump($args);
    $api_client->milestone($args);
}
main();
?>