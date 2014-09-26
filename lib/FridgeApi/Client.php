<?php
namespace FridgeApi;

class Client
{
    public $base = "http://api.fridgecms.com/v1";
    public $debug = false;
    protected $agent, $access_token, $refresh_token, $api_key, $api_secret;

    public function __construct($api_key, $api_secret = "", $custom_base_url = false)
    {
        $this->agent = new \GuzzleHttp\Client();
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        if ($custom_base_url) $this->base = $custom_base_url;
    }

    public function authenticate()
    {
        $res = $this->request("POST", "oauth/token", array(
            'grant_type' => "client_credentials",
            'client_id' => $this->api_key,
            'client_secret' => $this->api_secret
        ), false)->json();
        $this->access_token = $res['access_token'];
        if (isset($res['refresh_token'])) $this->refresh_token = $res['refresh_token'];
    }

    public function get($path)
    {
        return $this->response_to_model($this->request("GET", $path));
    }

    public function post($path, $data)
    {
        return $this->response_to_model($this->request("POST", $path, $data));
    }

    public function put($path, $data)
    {
        return $this->response_to_model($this->request("PUT", $path, $data));
    }

    public function delete($path)
    {
        return $this->response_to_model($this->request("DELETE", $path));
    }

    public function response_to_model($res)
    {
        try {
            $body = $res->json();

            if (is_array($body) && !Util::is_assoc_array($body)) {
                return array_map(function($item) {
                    return new Model($item);
                }, $body);
            }

            return new Model($body);
        } catch (\Exception $e) {
            if ($this->debug) error_log("Fridge API Exception -- Unable to create Model from response. -- " . $e->getMessage());
            return false;
        }
    }

    public function request($method, $path, $data=null, $auth=true)
    {
        $req = $this->agent->createRequest($method, $this->base."/".$path);
        if ($method == "POST" || $method == "PUT") {
            $req = $this->agent->createRequest($method, $this->base."/".$path, array(
                'body' => $data
            ));
        }
        if ($auth) {
            if (!$this->access_token) $this->authenticate();
            $req->setHeader('Authorization', 'token ' . $this->access_token);
        }

        if ($this->debug) error_log($req->__toString());

        try {
            return $this->agent->send($req);
        } catch(\Exception $e) {
            if ($this->debug) error_log("Fridge API Exception -- " . $e->getMessage());
            return false;
        }
    }

// private

}
