<?php
require "TransactionCurrency.php";

public class PlaynomicsApiClient {
    private $app_id;
    private $app_secret;
    private $http_options;

    public $test_mode = false;

    public function __construct($app_id, $app_secret, $http_options = null) {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->http_options = $http_options;
    }

    public function sessionStart($args) {
        $path = "/v1/sessionStart";
        $params = $this->getDefaultParams($args["user_id"]);
        $params["s"] = $args["session_id"];
        $params["ss"] = $this->coaleseValue($args, "site");
        return $this->sendRequest($path, $params);
    }


    public function sessionEnd($args) {
        $path = "/v1/sessionEnd";

        $params = $this->getDefaultParams($args["user_id"]);
        $params["s"] = $args["session_id"];
        $params["r"] = $this->coaleseValue($args, "reason");
        return $this->sendRequest($path, $params);
    }
    
    public function gameStart($args) {
        $path = "/v1/gameStart";

        $params = $this->getDefaultParams($args["user_id"]);
        
        $params["g"] = $this->coaleseValue($args, "instance_id");
        $params["s"] = $this->coaleseValue($args, "session_id");
        $params["ss"] = $this->coaleseValue($args, "site");
        $params["gi"] = $this->coaleseValue($args, "game_id");
        $params["gt"] = $this->coaleseValue($args, "type");
        return $this->sendRequest($path, $params);
    }

    public function gameEnd($args) {
        $path = "/v1/gameEnd";

        $params = $this->getDefaultParams($args["user_id"]);
        $params["g"] = $this->coaleseValue($args, "instance_id");
        $params["s"] = $this->coaleseValue($args, "session_id");
        $params["gr"] = $this->coaleseValue($args, "reason");
        return $this->sendRequest($path, $params);
    }

    public function transaction($args) {
        $path = "/v1/transaction";

        $params = $this->getDefaultParams($args["user_id"]);
        $params["r"] = $args["transaction_id"];
        $params["tt"] = $args["type"];

        $params["i"] = $this->coaleseValue($args, "item_id");
        $params["tq"] = $this->coaleseValue($args, "quantity");
        $params["to"] =  $this->coaleseValue($args, "other_user_id");

        $index = 1;
        foreach($args["currencies"] as $currency) {
            $params["tc".$index] = $currency->getName();
            $params["tv".$index] = $currency->getValue();
            $params["ta".$index] = $currency->getCategory();
            $index ++;
        }
        return $this->sendRequest($path, $params);
    }

    public function milestone($args) {
        $path = "/v1/milestone";

        $params = $this->getDefaultParams($args["user_id"]);
        $params["mi"] = $args["milestone_id"];
        $params["mn"] = $args["milestone_name"];
        
        return $this->sendRequest($path, $params);
    }

    public function userInfo($args) {
        $path = "/v1/userInfo";

        $params = $this->getDefaultParams($args["user_id"]);
        $info_type = "update";
        $params["pt"] = $info_type;
        $params["pc"] = $this->coaleseValue($args, "country");
        $params["ps"] = $this->coaleseValue($args, "subdivision");
        $params["px"] = $this->coaleseValue($args, "sex");
        $params["pb"] = $this->coaleseValue($args, "birth_year");
        $params["po"] = $this->coaleseValue($args, "source");
        $params["pm"] = $this->coaleseValue($args, "source_campaign");
        $params["pu"] = $this->coaleseValue($args, "source_user");
        $params["pi"] = $this->coaleseValue($args, "install_time");

        return $this->sendRequest($path, $params);
    }

    public function invitationSent($args) {
        $path = "/v1/invitationSent";

        $params = $this->getDefaultParams($args["user_id"]);
        $params["ii"] = $args["invitation_id"];
        $params["ir"] = $this->coaleseValue($args, "recipient_user_id");
        $params["ia"] = $this->coaleseValue($args, "recipient_address");
        $params["im"] = $this->coaleseValue($args, "method");
        return $this->sendRequest($path, $params);
    }

    public function invitationResponse($args) {
        $path = "/v1/invitationResponse";
        $params = $this->getDefaultParams($args["user_id"]);
        $params["ii"] = $args["invitation_id"];
        $params["ir"] = $this->coaleseValue($args, "recipient_user_id");
        
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

    private coaleseValue($array, $key) {
        return array_key_exists($key, $array) ? $array[$key] : null;
    }

    private function sendRequest($path, $query_params) {
        $base_url = $this->test_mode 
            ? "http://api.b.playnomics.com" 
            : "http://api.a.playnomics.com";
        
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

        $request_url = $base_url . $path;
        
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