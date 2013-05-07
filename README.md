PlayRM RESTful API Documentation
================================

This guide showcases the features of the PlayRM RESTful API, provides a sample API client written in PHP, and documents what information can be sent via server-side requests.

PlayRM provides game developers with tools for tracking player behavior and engagement so that they can:

* Better understand and segment their audience
* Reach out to new like-minded players
* Retain their current audience
* Ultimately generate more revenue for their games

<img src="http://www.playnomics.com/wp-content/uploads/2013/03/header-flow-chart-02.png"/>

Using the PlayRM RESTful API provides you with the flexibility to leverage PlayRM accross multiple games in conjunction with existing systems eg: payments, registration. In some cases, this may simplify your integration with PlayRM.

**The engagement module and messaging are not available via the RESTful API. To utilize this functionality, you'll need to implement the appropriate client SDK in your game client.**

* [User Info Module](#demographics-and-install-attribution) - provides basic user information
* [Monetization Module](#monetization) - tracks various monetization events
* [Viral Module](#invitations-and-virality) - tracks the social activities of users
* [Milestone Module](#custom-event-tracking) - tracks pre-defined significant events in the game experience

Core Concepts
=============
* [Prerequisites](#prerequisites)
    * [Signing Up for the PlayRM Service](#signing-up-for-the-playrm-service)
    * [Register Your Game](#register-your-game)
# [Server-Side Integration](#server-side-integration)    
    * [Common Parameters](#common-parameters)
    * [Signing Requests](#signing-requests)
    * [Demographics and Install Attribution](#demographics-and-install-attribution)
    * [Monetization](#monetization)
        * [Purchases of In-Game Currency with Real Currency](#purchases-of-in-game-currency-with-real-currency)
        * [Purchases of Items with Real Currency](#purchases-of-items-with-real-currency)
        * [Purchases of Items with Premium Currency](#purchases-of-items-with-premium-currency)
    * [Invitations and Virality](#invitations-and-virality)
    * [Custom Event Tracking](#custom-event-tracking)
* [Support Issues](#support-issues)

Prerequisites
=============
Before you can integrate with the PlayRM SDK you'll need to sign up and register your game.

## Signing Up for the PlayRM Service

Visit <a href="https://controlpanel.playnomics.com/signup" target="_blank">https://controlpanel.playnomics.com/signup</a> to create an account. The control panel is the dashboard to manage all of the PlayRM features once the SDK integration has been completed.

## Register Your Game
After receiving a registration confirmation email, login to the <a href="https://controlpanel.playnomics.com" target="_blank">control panel</a>. Select the "Applications" tab and create a new application. Your application will be granted an `App ID` and an `API Key`.

Server-Side Integration
=======================

## Common Parameters

For every reqest to the API, we always required that you submit the following parameters:

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
            <td><code>Timestamp</code></td>
            <td>32-bit signed integer, 64-bit signed integer, or 64-bit double</td>
            <td>
                The time an event took place. The value may be one of the following:
                
                <ul>
                    <li>Unix epoch time in seconds since Jan 1, 1970 i.e., <em>ssssssssss</em></li>
                    <li>Unix epoch time in seconds since Jan 1, 1970 with milliseconds as a decimal, i.e., <em>ssssssssss.mmm</em></li>
                    <li>Unix epoch time in milliseconds since Jan 1, 1970, i.e., <em>ssssssssssmmm</em></li>
                </ul>
                
                Values > 2147483647 or < -2147483648 are assumed to be epoch time in milliseconds and may not contain a decimal component.  To indicate times prior to Jan 1, 1970, use a negative timestamp.
            </td>
        </tr>
        <tr>
            <td><code>App ID</code></td>
            <td>32-bit signed integer</td>
            <td>Playnomics-assigned identifier for the application in which an event occurred</td>
        </tr>
        <tr>
            <td><code>User ID</code></td>
            <td>String, 64 char max, UTF-8</td>
            <td>
                The <code>User ID</code> should be a persistent, anonymized, and unique to each player.
                
                You can also use third-party authorization tool like Facebook or Twitter. However, <strong>you cannot use the user's Facebook ID or any personally identifiable information (plain-text email, name, etc) for the <code>User ID</code>.</strong>
            </td>
        </tr>
    </tbody>
</table>

## Signing Requests

You should sign each request with your `API Key` to ensure that only events originating from your application are accepted by the API. This is done by appending an additional signature parameter, `sig`. The signature process is very similar to that of <a href="https://tools.ietf.org/html/draft-ietf-oauth-v2-12" target="_blank">OAuth 2.0</a>. The signature process requires a shared key, the `API Key`.

Process to sign a request:
* Generate the canonical URL
    * Start with the URI path from the first "/" after the hostname
    * Remove any signature parameters (e.g., sig) if they exist
    * Re­order the query parameters to be in case­insensitive alphabetical order
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

The user info module can be called to collect basic demographic and acquisition information. This data can be used to segment users based on how/where they were acquired, and enables improved targeting with basic demographics in addition to the behavioral data collected using other events.

PlayRM collects all user info events for the same userId and coalesces the information in the events to build an information catalog for that user. In the case of a conflict (such as two events for the same userId with different country parameters), Playnomics uses the information from the event with the most recent timestamp parameter. Since users may have been created prior to instrumentation, it is suggested that you send a user info event at the start of each user's session (e.g., upon login) to update that user's catalog information and to increase coverage of players in the catalog.

API Path: `/v1/userInfo`

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
            <td>Timestamp</td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>t</code></td>
        </tr>
        <tr>
            <td>App ID</td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>a</code></td>
        </tr>
        <tr>
            <td>User ID</td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>u</code></td>
        </tr>
        <tr>
            <td>Type</td>
            <td>Optional</td>
            <td>String</td>
            <td>Must be update.</td>
            <td><code>pt</code></td>
        </tr>
        <tr>
            <td>Country</td>
            <td>Optional</td>
            <td>String, 2 char max, ASCII</td>
            <td>2 letter country code, described in <a href="http://en.wikipedia.org/wiki/ISO_3166-1" target="_blank">ISO_3166-1</a></td>
            <td><code>pc</code></td>
        </tr>
        <tr>
            <td>Subdivision</td>
            <td>Optional</td>
            <td>String, 3 char max, ASCII</td>
            <td>2-3 letter subdivision/state code, described in <a href="http://en.wikipedia.org/wiki/ISO_3166-2" target="_blank">ISO 3166-2</a></td>
            <td>ps</td>
        </tr>
        <tr>
            <td>Sex</td>
            <td>Optional</td>
            <td>String, 1 character</td>
            <td>
                The user's sex. Must be one of the following:
                
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
            <td>Birth Year</td>
            <td>Optional</td>
            <td>String, 10 char max</td>
            <td>
                The user's birthyear (and optionally month).  Must be one of the following formats: 
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
            <td>Source</td>
            <td>Optional</td>
            <td>String, 16 char max, ASCII</td>
            <td>
                Referring source for this user. May be application-defined, however the following standard case-insensitive names are highly suggested:
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
            <td>Source Campaign</td>
            <td>Optional</td>
            <td>String, 16 char max, ASCII</td>
            <td>Application-defined identifier for the campaign that referred this user</td>
            <td><code>pm</code></td>
        </tr>
        <tr>
            <td>Source User</td>
            <td>Optional</td>
            <td>See User Id Common Parameter </td>
            <td>If the was acquired via a UserReferral, the User Id of the person who initially brought the user into the application</td>
            <td>pu</td>
        </tr>
        <tr>
            <td>Install Time</td>
            <td>32-bit signed integer, 64-bit signed integer, 64-bit double, or String</td>
            <td>Time or date on which this user was first created in the application - could be the time the application was installed, the time at which Facebook permissions were granted, or an application-defined time. If not provided, assumed to be the timestamp of the earliest event for the User Id.</td>
            <td>Same format as the timestamp parameter (for times).</td>
            <td>pi</td>
        </tr>
    </tbody>
</table>

Example URL Call:

```
http://api.b.playnomics.net/v1/userInfo?
    a=4104146557547035721&
    pb=1980&
    pc=US&
    pi=1367896148&
    pm=UserReferral&
    po=invitation&
    ps=US-CA&
    pt=update&
    pu=1&
    px=F&
    t=1367896148&
    u=2&
    sig=cd1eabcca9719172be9206f77b411f05085e8bd2890d2e764c331b4f40805a76
```

Call with PHP sample client:

```php
//report the demographic info that the player fills out when she joins

$args = array(
    "user_id" => $player_ID,
    "country" => "US",
    "subdivision" => "US-CA",
    "sex" => "F",
    "birth_year" => 1980,
    "source" => "invitation",
    "source_user" => $player_one_id,
    "source_campaign" => "UserReferral",
    "install_time" => time()
);

$api_client->userInfo($args);
```
## Monetization

PlayRM provides a flexible interface for tracking monetization events. This module should be called every time a player triggers a monetization event. 

This event tracks users that have monetized and the amount they have spent in total, real currency:
* FBC (Facebook Credits)
* USD (US Dollars)
* OFD (offer valued in USD)
or an in-game *virtual* currency.

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
            <td>Timestamp</td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>t</code></td>
        </tr>
        <tr>
            <td>App ID</td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>a</code></td>
        </tr>
        <tr>
            <td>User ID</td>
            <td>Yes</td>
            <td>See description above.</td>
            <td>See description above.</td>
            <td><code>u</code></td>
        </tr>
        <tr>
            <td>Transaction Id</td>
            <td>Yes</td>
            <td>64-bit signed integer</td>
            <td>
                Unique identifier for this transaction. The tuple (User Id, Transaction Id, Item Id) should be unique. However it may be duplicated if a transaction is between two users (such as a sale or gift)
            </td>
            <td><code>r</code></td>
        </tr>
        <tr>
            <td>Item Id</td>
            <td>Optional</td>
            <td>String, 64 char max, ASCII  </td>
            <td>
                Application-assigned unique identifier for the item involved in this transaction. If the Type parameter is CurrencyConvert or the transaction involves only currency (e.g., an initial allocation of credits), this parameter may be omitted. Applications may, however, use the itemId parameter to track different types or buckets of currency conversions.
            </td>
            <td><code>i</code></td>
        </tr>
        <tr>
            <td>Quantity</td>
            <td>Optional</td>
            <td>64-bit double</td>
            <td>The number of items (referenced by the itemId parameter) involved in this transaction. If the Type parameter is CurrencyConvert or the transaction involves only currency, this parameter may be omitted.</td>
            <td><code>tq</code></td>
        </tr>
        <tr>
            <td>Type</td>
            <td>Yes</td>
            <td>String</td>
            <td>
                The type of transaction. Must be one of the following: 
                <ul>
                    <li>BuyItem: A purchase of virtual item.  The quantity is added to the user's inventory</li>
                    <li>SellItem: A sale of a virtual item to another user. The item is removed from the user's inventory. Note: a sale of an item will result in two events with the same transactionId, one for the sale with type SellItem, and one for the receipt of that sale, with type BuyItem</li>
                    <li>ReturnItem: A return of a virtual item to the store. The item is removed from the user's inventory</li>
                    <li>BuyService: A purchase of a service, e.g., VIP membership </li>
                    <li>SellService: The sale of a service to another user</li>
                    <li>ReturnService:  The return of a service</li>
                    <li>CurrencyConvert: An conversion of currency from one form to another, usually in the form of real currency (e.g., US dollars) to virtual currency.  If the type of a transaction is CurrencyConvert, then there should be at least 2 currencyTypeN parameters</li>
                    <li>Initial: An initial allocation of currency and/or virtual items to a new user</li>
                    <li>Free: Free currency or item given to a user by the application</li>
                    <li>Reward: Currency or virtual item given by the application as a reward for some action by the user</li>
                    <li>GiftSend: A virtual item sent from one user to another. Note: a virtual gift should result in two transaction events with the same transactionId, one with the type GiftSend, and another with the type GiftReceive</li>
                    <li>GiftReceive: A virtual good received by a user. See note for GiftSend type</li>
                </ul>
            </td>
            <td><code>tt</code></td>
        </tr>
        <tr>
            <td>Currency Type {N}(0 <= N <= 9)</td>   
            <td>Yes</td>
            <td>String, 16 char max, ASCII.</td>     
            <td>
                A currency type involved in this transaction. One Currency Type {N} parameter is required, but there may be up to 10 Currency Type {N} parameters in a transaction event. If the Currency Category {N} parameter is r then the currency type must be one of the following:
                <ul>
                    <li>USD for US dollars</li>
                    <li>FBC for Facebook Credits</li>
                    <li>OFD for offer payment in US dollars</li>
                    <li>OFF for offer payment in unspecified units</li>
                </ul>
                Future versions of the API will support a wider range of currencies, e.g., ISO-4217 codes
            </td>
            <code>tc{N}</code>
        </tr>
        <tr>
            <td>Currency Value {N} (0 <= N <= 9)   
            <td>Yes 
            <td>64-bit double   
            <td>
                The amount of Currency Type {N} involved in this transaction. At least, one Currency Value {N} parameter is required. The value of the Currency Value {N}  parameter should be positive in all cases except for when the type of the transaction is CurrencyConvert; in that case, the value for the currency the user is spending should be negative.
            </td>
            <td><code>tvN</code></td>
        </tr>
        <tr>
            <td>Currency Category {N} (0 <= N <= 9)</td>
            <td>Yes</td>
            <td>String, one character</td>
            <td>
                The category of currency involved in this transaction. At least one Currency Category {N} parameter is required. Must be one of the following:
                <ul>
                    <li> r: real currency (e.g., US dollars or Facebook Credits)</lI>
                    <li> v: virtual currency</li>
                </ul>
            </td>
            <td><code>taN</code></td>
        </tr>
        <tr>
            <td>Other User Id</td>    
            <td>Optional</td>    
            <td>String, 64 char max, UTF-8</td>  
            <td>
                The UserId of another user involved in this transaction.  E.g., the recipient of a gift, or the buyer of a sold item
            </td>
            <td><code>to</code></td>
        </tr>
    </tbody>
</table>



We hightlight three common use-cases below.
* [Purchases of In-Game Currency with Real Currency](#purchases-of-in-game-currency-with-real-currency)
* [Purchases of Items with Real Currency](#purchases-of-items-with-real-currency)
* [Purchases of Items with In-Game Currency](#purchases-of-items-with-in-game-currency)

### Purchases of In-Game Currency with Real Currency


### Purchases of Items with Real Currency


### Purchases of Items with Premium Currency



## Invitations and Virality
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
           <td>

           </td>
           <td>

           </td>
           <td>
           </td>
           <td>
           </td>
        </tr>
    </tbody>
</table>

## Custom Event Tracking

<table>
    <thead>
        <tr>
            <th>
                
            </th>
            <th>

            </th>
            <th>

            </th>
            <th>

            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>

            </td>
            <td>

            </td>
            <td>
            </td>
            <td>
            </td>
        </tr>
    </tbody>
</table>
# Support Issuess