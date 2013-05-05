<?php
require "TransactionCurrency.php";

public class PlaynomicsApiClient {
    private $app_id;
    private $app_secret;
    private $http_options;

    public function __construct($app_id, $app_secret, $http_options = null) {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->http_options = $http_options;
    }

    public function sessionStart($user_id, $session_id, $site = null) {
        $path = "/v1/sessionStart";
        $params = $this->getDefaultParams($user_id);
        $params["s"] = $session_id;
        $params["ss"] = $site;
        return $this->sendRequest($path, $params);
    }

    public function sessionEnd($user_id, $session_id, $reason = null) {
        $path = "/v1/sessionEnd";

        $params = $this->getDefaultParams($user_id);
        $params["s"] = $session_id;
        $params["r"] = $reason;
        return $this->sendRequest($path, $params);
    }
    
    public function gameStart($user_id, $instance_id = null, $session_id = null, $site = null, $type = null, $game_id = null) {
        $path = "/v1/gameStart";

        $params = $this->getDefaultParams($user_id);
        
        $params["g"] = $instance_id;
        $params["s"] = $session_id;
        $params["ss"] = $site;
        $params["gi"] = $game_id;
        $params["gt"] = $type;
        return $this->sendRequest($path, $params);
    }

    public function gameEnd($user_id, $instance_id = null, $session_id = null,  $reason = null) {
        $path = "/v1/gameEnd";

        $params = $this->getDefaultParams($user_id);
        $params["g"] = $instance_id;
        $params["s"] = $session_id;
        $params["gr"] = $reason;
        return $this->sendRequest($path, $params);
    }

    public function transaction($user_id, $transaction_id, $item_id = null, $quantity = null, $type = null, $other_user_id = null, $currencies) {
        $path = "/v1/transaction";

        $params = $this->getDefaultParams($user_id);
        $params["r"] = $transaction_id;
        $params["i"] = $item_id;
        $params["tq"] = $quantity;
        $params["tt"] = $type;
        $params["to"] = $other_user_id;

        $index = 1;
        foreach($currencies as $currency) {
            $params["tc".$index] = $currency->getName();
            $params["tv".$index] = $currency->getValue();
            $params["ta".$index] = $currency->getCategory();
            $index ++;
        }
        return $this->sendRequest($path, $params);
    }

    public function milestone($user_id, $milestone_id, $milestone_name) {
        $path = "/v1/milestone";

        $params = $this->getDefaultParams($user_id);
        $params["mi"] = $milestone_id;
        $params["mn"] = $milestone_name;
        
        return $this->sendRequest($path, $params);
    }

    public function userInfo($user_id, $country = null, $subdivision = null, $sex = null, $birth_year = null, $source = null, $source_campaign = null,
        $source_user = null, $install_time = null) {
        $path = "/v1/userInfo";

        $params = $this->getDefaultParams($user_id);
        $info_type = "update";
        $params["pt"] = $info_type;
        $params["pc"] = $country;
        $params["ps"] = $subdivision;
        $params["px"] = $sex;
        $params["pb"] = $birth_year;
        $params["po"] = $source;
        $params["pm"] = $source_campaign;
        $params["pu"] = $source_user;
        $params["pi"] = $install_time;

        return $this->sendRequest($path, $params);
    }

    public function invitationSent($user_id, $invitation_id, $recipient_user_id = null, $recipient_address = null, $method = null) {
        $path = "/v1/invitationSent";

        $params = $this->getDefaultParams($user_id);
        $params["ii"] = $invitation_id;
        $params["ir"] = $recipient_user_id;
        $params["ia"] = $recipient_address;
        $params["im"] = $method;
        return $this->sendRequest($path, $params);
    }

    public function invitationResponse($user_id, $recipient_user_id) {
        $path = "/v1/invitationResponse";
        $params = $this->getDefaultParams($user_id);
        $params["ii"] = $invitation_id;
        $params["ir"] = $recipient_user_id;
        
        $invitation_response = "accepted";
        $params["ie"] = $invitation_response;
        return $this->sendRequest($path, $params);
    }

    private function getDefaultParams($user_id) {
        return array(
            "a" => $this->app_id,
            "u" => $user_id,
            "t" => getTimeStamp()
        );
    }

    private function getTimeStamp() {
        //get the current timezone
        $current_timezone = date_default_timezone_get();
        //temporarily set the timezone to UTC
        date_default_timezone_set("UTC");
        $time = time();
        //reset the timezone
        date_default_timezone_set($current_timezone);
        return $time;
    }

    private function sendRequest($path, $query_params) {
        $base_url = "http://api.a.playnomics.com";
        
        $has_params = $query_params && count($query_params) > 0;
        if($has_params) {
            //sort the array by its keys
            ksort($query_params);
            $path .= "?";
            foreach($query_params as $key => $value) {
                if($key && $key != "" && $value && $value != ""){
                    $path .= "&". urlencode($key) . "=".urlencode($value);
                }
            }
        }

        $signature = hash_hmac("sha256", $path, $this->app_secret);
        $path .= ($has_params ? "&" : "?") . "sig=" . $signature;

        $request_url = $base_url . $path
        
        $response = $this->http_options
            ? http_get($request_url, $this->http_options) 
            : http_get($request_url);

        if(!$response) {
            error_log("Could not make request to " . $request_url);
        }
        return $response;
    }
}
?>