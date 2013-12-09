PlayRM RESTful API Documentation
================================

If you're new to PlayRM or don't yet have an account with <a href="http://www.playnomics.com">Playnomics</a>, please take a moment to <a href="http://integration.playnomics.com/technical/#integration-getting-started">get acquainted with PlayRM</a>.

This guide showcases the features of the PlayRM RESTful API, provides a sample API client written in PHP, and documents what information can be sent via server-side requests.

PlayRM provides game publishers with tools for tracking player behavior and engagement so that they can:

* Better understand and segment their audience
* Reach out to new, like-minded players
* Retain their current audience
* Ultimately generate more revenue for their games

Using the PlayRM RESTful API provides you with the flexibility to leverage PlayRM across multiple games in conjunction with existing systems, such as payments and registration. If you already have an existing game server, this may simplify your integration with PlayRM.

The client SDKs do provide some additional functionality that is not available with a pure server-side integration:
* Segmented messaging
* A more robust Engagement module which allows PlayRM to track and score player intensity
* Geo location of the player

To access this functionality you'll want to implement the appropriate client-side SDK in your game client (JavaScript, iOS, Unity, Android, etc). The detailed engagement module and the player geo location information are automatic once the SDK has been installed.

These modules are available by calling the RESTful API:
 
* [UserInfo Module](#demographics-and-install-attribution) - provides basic user information
* [Monetization Module](#monetization) - tracks various monetization events
* [Milestone Module](#custom-event-tracking) - tracks pre-defined significant events in the game experience

Outline
=======
* [Server-Side Integration](#server-side-integration)    
    * [Common Parameters](#common-parameters)
    * [Instantiating the PHP Client](#instantiating-the-php-client)
    * [Signing Requests](#signing-requests)
    * [Demographics and Install Attribution](#demographics-and-install-attribution)
    * [Monetization](#monetization)
        * [Purchases of In-Game Currency with Real Currency](#purchases-of-in-game-currency-with-real-currency)
        * [Purchases of Items with Real Currency](#purchases-of-items-with-real-currency)
        * [Purchases of Items with Premium Currency](#purchases-of-items-with-premium-currency)
    * [Custom Event Tracking](#custom-event-tracking)
* [Support Issues](#support-issues)

Server-Side Integration
=======================

## Common Parameters

For every request sent to the API, we require you to submit the following parameters:

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><em>timestamp</em></td>
            <td>32-bit signed integer, 64-bit signed integer, or 64-bit double</td>
            <td>
                The time an event took place. The value may be one of the following:
                <ul>
                    <li>Unix epoch time in seconds since Jan 1, 1970 i.e., <em>ssssssssss</em></li>
                    <li>Unix epoch time in seconds since Jan 1, 1970 with milliseconds as a decimal, i.e., <em>ssssssssss.mmm</em></li>
                    <li>Unix epoch time in milliseconds since Jan 1, 1970, i.e., <em>ssssssssssmmm</em></li>
                </ul>

                Values &gt; 2147483647 or &lt; -2147483648 are assumed to be epoch time in milliseconds and may not contain a decimal component. To indicate times prior to Jan 1, 1970, use a negative timestamp.
            </td>
        </tr>
        <tr>
            <td><em>appId</em></td>
            <td>32-bit signed integer</td>
            <td>Playnomics-assigned identifier for the application in which an event occurred</td>
        </tr>
        <tr>
            <td><em>userId</em></td>
            <td>String, 64 char max, UTF-8</td>
            <td>
                The <em>User ID</em> should be a persistent, anonymized, and unique identifier to each player.
                
                You can also use third-party authorization providers like Facebook or Twitter. However, <strong>you cannot use the player's Facebook ID or any personally identifiable information (plain-text email, name, etc) for the <em>User ID</em>.</strong>
            </td>
        </tr>
    </tbody>
</table>

## Instantiating the PHP Client

To use the PHP PlaynomicsApiClient, fork this repository or download the source files. You can then import the PlaynomicsApiClient into your server-side code:

```php
require_once "<PATH-TO-PLAYNOMICS-API-CLIENT>/PlaynomicsApiClient.php";

//...
//...
//...

//optionally set a proxy to send requests through
$proxy = "localhost:8888";

$api_client = new PlaynomicsApiClient($app_id, $api_key, $proxy);

//when testing, you can point the client to our testing API so that events won't get logged in the control panel
//dashboard

$api_client->test_mode = true;
```

The `$proxy` variable is entirely optional. You should make sure that `$api_client->test_mode` is set to `false` when you are ready to deploy your code to production.

## Signing Requests

You should sign each request with your `API Key` to ensure that only events originating from your application are accepted by the API. This is done by appending an additional signature parameter, `sig`. The signature process is very similar to that of <a href="https://tools.ietf.org/html/draft-ietf-oauth-v2-12" target="_blank">OAuth 2.0</a>. The signature process requires a shared key, the `API Key`.

Process to sign a request:
* Generate the canonical URL
    * Start with the URI path from the first "/" after the hostname
    * Remove any signature parameters (e.g., sig) if they exist
    * Re­order the query parameters to be in case-­insensitive, alphabetical order
* Compute the signature using HMAC+SHA256 with your application's `API Key` and the canonical url string from the previous step
* Append the signature as a the `sig` parameter, but make sure that you URL encode the value

`PlaynomicsApiClient` demonstrates how to do this:

```php
private function sendRequest($path, $query_params) {
    $base_url = $this->test_mode
        ? "http://api.b.playnomics.net"
        : "http://api.a.playnomics.net";
    
    $has_params = $query_params && count($query_params) > 0;
    
    if($has_params) {
        //sort the array by its keys
        ksort($query_params);
        $first_param = true;
        foreach($query_params as $key => $value) {
            if(isset($value) && $value != ""){
                $path .=  ($first_param ? "?" : "&") . urlencode($key) . "=" . urlencode($value);
                $first_param = false;
            }
        }
    }

    $signature = hash_hmac("sha256", $path, $this->api_key);
    $path .= ($has_params ? "&" : "?") . "sig=" . urlencode($signature);

    $request_url = $base_url . $path;
        
    $curl_get = curl_init();
    curl_setopt_array($curl_get, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $request_url,
        //fail on anything where the HTTP status code is 400 or higher
        CURLOPT_FAILONERROR => 1
    ));

    if($this->proxy){
        curl_setopt($curl_get, CURLOPT_PROXY, $this->proxy);
    }
        
    $response = curl_exec($curl_get);

    if(!$response) {
        error_log("Could not make request to " . $request_url);
        error_log('Error: "' . curl_error($curl_get) . '" - Code: ' . curl_errno($curl_get));
    }

    curl_close($curl_get);
    return $response;
}
```

## Demographics and Install Attribution

The userInfo module can be called to collect basic demographic and acquisition information. This data can be used to segment users based on how/where they were acquired, and enables improved targeting with basic demographics in addition to the behavioral data collected using other events.

PlayRM collects all userInfo events for the same userId and coalesces the information in the events to build an information catalog for that player. In the case of a conflict (such as two events for the same userId with different country parameters), Playnomics uses the information from the event with the most recent timestamp parameter. Since players may have been created prior to instrumentation, it is suggested that you send a userInfo event at the start of each player's session (e.g., upon login) to update that player's catalog information and to increase coverage of players in the catalog.

API Path: `/v1/userInfo`
Query Parameters:
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Req?</th>
            <th>Type</th>
            <th>Description</th>
            <th>URL Parameter</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><em>timestamp</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>t</code></td>
        </tr>
        <tr>
            <td><em>appId</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>a</code></td>
        </tr>
        <tr>
            <td><em>userId</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>u</code></td>
        </tr>
        <tr>
            <td><em>type</em></td>
            <td>Optional</td>
            <td>String</td>
            <td>Must be "update".</td>
            <td><code>pt</code></td>
        </tr>
        <tr>
            <td><em>sex</em></td>
            <td>Optional</td>
            <td>String, 1 character</td>
            <td>
                The player's sex. Must be one of the following:
                <ul>
                    <li>M: male</li>
                    <li>F: female</li>
                    <li>U: unknown</li> 
                </ul>

                If omitted, assumed to be U.
            </td>
            <td><code>px</code></td>  
        </tr>
        <tr>
            <td><em>birthYear</em></td>
            <td>Optional</td>
            <td>String, 10 char max</td>
            <td>
                The player's <em>birthYear</em> (and optionally month).  Must be one of the following formats: 
                <ul>
                    <li>YYYY/MM</li>
                    <li>YYYY-MM</li>
                    <li>MM/YYYY</li>
                    <li>YYYY</li>
                </ul>
                Year must be greater than 1900 and less than/equal to the current year + 1.
            </td>
            <td><code>pb</code></td>
        </tr>
        <tr>
            <td><em>source</em></td>
            <td>Optional</td>
            <td>String, 16 char max, ASCII</td>
            <td>
                Referring source for this player. May be application-defined, however the following standard case-insensitive names are highly suggested:
                <ul>
                    <li>Paid advertising: Adwords, DoubleClick, YahooAds, MSNAds, AOLAds, Adbrite, FacebookAds</li>
                    <li>Search: GoogleSearch, YahooSearch, BingSearch, FacebookSearch</li>
                    <li>Networks: Applifier, AppStrip, VIPGamesNetwork</li>
                    <li>Social: UserReferral</li>
                    <li>Other: InterGame</li>
                </ul>
            </td>
            <td><code>po</code></td> 
        </tr>
        <tr>
            <td><em>sourceCampaign</em></td>
            <td>Optional</td>
            <td>String, 16 char max, ASCII</td>
            <td>Application-defined identifier for the campaign that referred this user</td>
            <td><code>pm</code></td>
        </tr>
        <tr>
            <td><em>sourceUser</em></td>
            <td>Optional</td>
            <td>See User Id Common Parameter </td>
            <td>
                If the was acquired via a UserReferral, the <em>userId</em> of the person who initially brought the user into the application
            </td>
            <td><code>pu</code></td>
        </tr>
        <tr>
            <td><em>installTime</em></td>
            <td>Optional</td>
            <td>
                32-bit signed integer, 64-bit signed integer, 64-bit double, or String
            </td>
            <td>
                Time or date on which this player was first created in the application - could be the time the application was installed, the time at which Facebook permissions were granted, or an application-defined time. If not provided, assumed to be the timestamp of the earliest event for the User Id.
                Same format as the timestamp parameter (for times).
            </td>
            <td><code>pi</code></td>
        </tr>
    </tbody>
</table>

Call with PHP REST Client:

```php
//report the demographic info that the player fills out when she joins

$args = array(
    "user_id" => $player_ID,
    "sex" => "F",
    "birth_year" => 1980,
    "source" => "invitation",
    "source_user" => $player_one_id,
    "source_campaign" => "UserReferral",
    "install_time" => time()
);

$api_client->userInfo($args);
```

Resulting HTTP GET:
```
http://api.a.playnomics.net/v1/userInfo?
    a=4104146557547035721&
    pb=1980&pi=1367945380&
    pm=UserReferral&
    po=invitation&
    pt=update&
    pu=1&
    px=F&
    t=1367945380&
    u=2&
    sig=a8234a1a12295c594ec570f94d5b37ca58025dd781f0f110af6b26ab041fb6c7
```

## Monetization

PlayRM provides a flexible interface for tracking monetization events. This module should be called every time a player triggers a monetization event. 

This event tracks players that have monetized and the amount they have spent in total, real currency:
* FBC (Facebook Credits)
* USD (US Dollars)
* OFD (offer valued in USD)
or an in-game *virtual* currency.

API Path: `/v1/transaction`
Query Parameters: 
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Req?</th>
            <th>Type</th>
            <th>Description</th>
            <th>URL Parameter</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><em>timestamp</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>t</code></td>
        </tr>
        <tr>
            <td><em>appID</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>a</code></td>
        </tr>
        <tr>
            <td><em>userId</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>u</code></td>
        </tr>
        <tr>
            <td><em>transactionId</em></td>
            <td>Yes</td>
            <td>64-bit signed integer</td>
            <td>
                Unique identifier for this transaction. The tuple (<em>userId</em>, <em>transactionId</em>, <em>itemId</em>) should be unique. However it may be duplicated if a transaction is between two players (such as a sale or gift)
            </td>
            <td><code>r</code></td>
        </tr>
        <tr>
            <td><em>itemId</em></td>
            <td>Optional</td>
            <td>String, 64 char max, ASCII</td>
            <td>
                Application-assigned unique identifier for the item involved in this transaction. If the <em>type</em> parameter is CurrencyConvert or the transaction involves only currency (e.g., an initial allocation of credits), this parameter may be omitted. Applications may, however, use the itemId parameter to track different types or buckets of currency conversions.
            </td>
            <td><code>i</code></td>
        </tr>
        <tr>
            <td><em>quantity</em></td>
            <td>Optional</td>
            <td>64-bit double</td>
            <td>The number of items (referenced by the itemId parameter) involved in this transaction. If the Type parameter is CurrencyConvert or the transaction involves only currency, this parameter may be omitted.</td>
            <td><code>tq</code></td>
        </tr>
        <tr>
            <td><em>type</em></td>
            <td>Yes</td>
            <td>String</td>
            <td>
                The type of transaction. Must be one of the following: 
                <ul>
                    <li>BuyItem: A purchase of virtual item. The quantity is added to the player's inventory</li>
                    <li>
                        SellItem: A sale of a virtual item to another player. The item is removed from the player's inventory. Note: a sale of an item will result in two events with the same transactionId, one for the sale with type SellItem, and one for the receipt of that sale, with type BuyItem
                    </li>
                    <li>
                        ReturnItem: A return of a virtual item to the store. The item is removed from the player's inventory
                    </li>
                    <li>BuyService: A purchase of a service, e.g., VIP membership </li>
                    <li>SellService: The sale of a service to another player</li>
                    <li>ReturnService:  The return of a service</li>
                    <li>
                        CurrencyConvert: An conversion of currency from one form to another, usually in the form of real currency (e.g., US dollars) to virtual currency.  If the type of a transaction is CurrencyConvert, then there should be at least 2 currencyTypeN parameters
                    </li>
                    <li>Initial: An initial allocation of currency and/or virtual items to a new player</li>
                    <li>Free: Free currency or item given to a player by the application</li>
                    <li>
                        Reward: Currency or virtual item given by the application as a reward for some action by the player
                    </li>
                    <li>
                        GiftSend: A virtual item sent from one player to another. Note: a virtual gift should result in two transaction events with the same transactionId, one with the type GiftSend, and another with the type GiftReceive
                    </li>
                    <li>GiftReceive: A virtual good received by a player. See note for GiftSend type</li>
                </ul>
            </td>
            <td><code>tt</code></td>
        </tr>
        <tr>
            <td><em>currencyTypeN</em> (0 &lt;= N &lt;= 9)</td>   
            <td>Yes</td>
            <td>String, 16 char max, ASCII.</td>     
            <td>
                A currency type involved in this transaction. One <em>currencyTypeN</em> parameter is required, but there may be up to 10 Currency Type {N} parameters in a transaction event. If the <em>currencyCategoryN</em>parameter is r then the currency type must be one of the following:
                <ul>
                    <li>USD for US dollars</li>
                    <li>FBC for Facebook Credits</li>
                    <li>OFD for offer payment in US dollars</li>
                    <li>OFF for offer payment in unspecified units</li>
                </ul>
                Future versions of the API will support a wider range of currencies, e.g., ISO-4217 codes
            </td>
            <td><code>tcN</code></td>
        </tr>
            <td><em>currencyTypeN</em> (0 &lt;= N &lt;= 9)</td>   
            <td>Yes</td>
            <td>64-bit double</td>
            <td>
                The amount of <em>currencyTypeN</em> involved in this transaction. At least, one currencyValueN parameter is required. The value of the <em>currencyValueN</em>  parameter should be positive in all cases except for when the type of the transaction is CurrencyConvert; in that case, the value for the currency the player is spending should be negative.
            </td>
            <td><code>tvN</code></td>
        </tr>
        <tr>
            <td><em>currencyCategoryN</em> (0 &lt;= N &lt;= 9)</td>
            <td>Yes</td>
            <td>String, one character</td>
            <td>
                The category of currency involved in this transaction. At least one <em>currencyCategoryN</em>  parameter is required. Must be one of the following:
                <ul>
                    <li> r: real currency (e.g., US dollars or Facebook Credits)</lI>
                    <li> v: virtual currency</li>
                </ul>
            </td>
            <td><code>taN</code></td>
        </tr>
        <tr>
            <td><em>otherUserId</em></td>
            <td>Optional</td>
            <td>String, 64 char max, UTF-8</td>
            <td>
                The <em>userId</em> of another player involved in this transaction, e.g. the recipient of a gift, or the buyer of a sold item
            </td>
            <td><code>to</code></td>
        </tr>
    </tbody>
</table>

We high light three common use-cases below.
* [Purchases of In-Game Currency with Real Currency](#purchases-of-in-game-currency-with-real-currency)
* [Purchases of Items with Real Currency](#purchases-of-items-with-real-currency)
* [Purchases of Items with In-Game Currency](#purchases-of-items-with-in-game-currency)

### Purchases of In-Game Currency with Real Currency

A very common monetization strategy is to incentivize players to purchase premium, in-game currency with real currency. PlayRM treats this like a currency exchange. This is one of the few cases where currency metadata: *currencyTypes*, *currencyValues*, *currencyCategories* are expressed in an array form. *itemId*, *quantity*, and *otherUserId* are not included.

Example: player purchases 500 Gold Coins for 10 USD.

Call with PHP REST Client:

```php
$args = array(
    "user_id" => $player_id,
    "transaction_id" => $transaction_id,
    "currencies" => array(
        0 => TransactionCurrency::createVirtual("Gold Coins", 500),
        1 => TransactionCurrency::createReal("USD", -10)
    ),
    "type" => TransactionType::CurrencyConvert
);

$api_client->transaction($args);
```

Resulting HTTP GET:
```
http://api.a.playnomics.net/v1/transaction?
    a=4104146557547035721&
    r=36&
    t=1367941381&
    ta0=v&
    ta1=r&
    tc0=Gold+Coins&
    tc1=USD&
    tt=CurrencyConvert&
    tv0=500&
    tv1=-10&
    u=1&
    sig=e58c38ecc4cde6a6b7522621f689895dd7c8d7efacb4d311fb2db75fa64fee48
```

### Purchases of Items with Real Currency

Example: player purchases a "Sword" for $.99 USD.

Call with PHP REST Client:

```php
$args = array(
    "user_id" => $player_id,
    "transaction_id" => $transaction_id,
    "currencies" => array(
        1 => TransactionCurrency::createReal("USD", .99)
    ),
    "type" => TransactionType::BuyItem,
    "quantity" => 1,
    "item_id" => "Sword"
);

$api_client->transaction($args);
```

Resulting HTTP GET:
```
http://api.a.playnomics.net/v1/transaction?
    a=4104146557547035721&
    i=Sword&
    r=43&
    t=1367941381&
    ta0=r&
    tc0=USD
    &tq=1&
    tt=BuyItem&
    tv0=0.99&
    u=1&
    sig=5eb51bccc53480d852c2cffccf83e87e33997587e2d817a5dd86c26b772778c0
```

### Purchases of Items with Premium Currency

This event is used to segment monetized players (and potential future monetizers) by collecting information about how and when they spend their premium currency (an in-game currency that is primarily acquired using a *real* currency). This is one level of information deeper than the previous use-cases.

#### Currency Exchanges

This is a continuation on the first currency exchange example. It showcases how to track each purchase of in-game *attention* currency (non-premium virtual currency) paid for with a *premium*:

Example: in this hypothetical, Energy is an attention currency that is earned over the lifetime of the game. They can also be purchased with the premium Gold Coins that the player may have purchased earlier. The player buys 100 Energy with 10 Gold Coins.

Call with PHP REST Client:
```php
$args = array(
    "user_id" => $player_id,
    "transaction_id" => $transaction_id,
    "currencies" => array(
        0 => TransactionCurrency::createVirtual("Gold Coins", -10),
        1 => TransactionCurrency::createVirtual("Energy", 100),
    ),
    "type" => TransactionType::CurrencyConvert,
);

$api_client->transaction($args);
```

Resulting HTTP GET:
```
http://api.a.playnomics.net/v1/transaction?
    a=4104146557547035721&
    r=83&
    t=1367941381&
    ta0=v&
    ta1=v&
    tc0=Gold+Coins&
    tc1=Energy&
    tt=CurrencyConvert&
    tv0=-10&
    tv1=100&
    u=1&
    sig=b214134241616786d1930555baf8c2606da39b8417cb8461102fef1633cf723f
```

#### Item Purchases

This is a continuation on the first item purchase example, except with premium currency.

Hypothetical: player buys 20 Light Armor with 5 Gold Coins.

Call with PHP REST Client:
```php
$args = array(
    "user_id" => $player_id,
    "transaction_id" => $transaction_id,
    "currencies" => array(
        1 => TransactionCurrency::createVirtual("Gold Coins", 5)
    ), 
    "type" => TransactionType::BuyItem,
    "quantity" => 20,
    "item_id" => "Light Armor"
);

$api_client->transaction($args);
```

Resulting HTTP GET:
```
http://api.a.playnomics.net/v1/transaction?
    a=4104146557547035721&
    i=Light+Armor&
    r=26&
    t=1367941381&
    ta0=v&
    tc0=Gold+Coins&
    tq=20&
    tt=BuyItem&
    tv0=5&
    u=1&
    sig=2ae9493eab0ca20683433048193f754d63bfbcf0c4dcbe1381ea9a3d6eb51e2a
```

## Custom Event Tracking

Milestones may be defined in a number of ways.  They may be defined at certain key gameplay points like finishing a tutorial, or may they refer to other important milestones in a player's lifecycle. PlayRM, by default, supports up to five custom milestones.  Players can be segmented based on when and how many times they have achieved a particular milestone.

API Path: `/v1/milestone`
Query Parameters: 
<table>
    <thead>
        <tr>
            <th>
                Name
            </th>
            <th>
                Required?
            </th>
            <th>
                Type
            </th>
            <th>
                Description
            </th>
            <th>
                URL Parameter
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><em>timestamp</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>t</code></td>
        </tr>
        <tr>
            <td><em>appID</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>a</code></td>
        </tr>
        <tr>
            <td><em>userId</em></td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>u</code></td>
        </tr>
        <tr>
            <td><em>milestoneId</em></td>
            <td>Yes</td>
            <td>64-bit signed integer</td>
            <td>a unique 64-bit numeric identifier for this milestone occurrence</td>
            <td><code>mi</code></td>  
        </tr>
        <tr>
            <td><em>milestoneName</em></td>
            <td>Yes</td>
            <td>String, ASCII</td>
            <td>
                The identifier for the milestone (case insensitive), must be CUSTOM1 through CUSTOM5. 
                The <em>milestoneName</em> is case-sensitive.
            </td>
            <td><code>mn</code></td>
        </tr>
    </tbody>
</table>

Call with PHP REST Client:
```php
$args = array(
    "user_id" => $player_id,
    "milestone_name" => "CUSTOM1",
    "milestone_id" => rand(1, 1000000)
);

$api_client->milestone($args);
```

Resulting HTTP GET
```
http://api.a.playnomics.net/v1/milestone?
    a=4104146557547035721&
    mi=63&
    mn=CUSTOM1&
    t=1367945380&
    u=1&
    sig=d678291fe62b9dc08f701fe7178d368c3f357bd390bfae53bf4bd2d9bf52ca00
```
<div id="support-issues">
    <h1>Support Issues</h1>
    If you have any questions or issues, please contact <a href="mailto:support@playnomics.com">support@playnomics.com</a>.
</div>