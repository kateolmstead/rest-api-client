<?php
class PlaynomicsApiClient {
    private $app_id;
    private $api_key;
    private $proxy;

    public $test_mode = false;

    public function __construct($app_id, $api_key, $proxy = null) {
        $this->app_id = $app_id;
        $this->api_key = $api_key;
        $this->proxy = $proxy;
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
            "t" => $this->getTimeStamp()
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

    private function coaleseValue($array, $key) {
        return array_key_exists($key, $array) ? $array[$key] : null;
    }

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
}
?>