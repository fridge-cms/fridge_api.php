<?php
namespace FridgeApi;

class Client
{
    public $base = "https://api.fridgecms.com/v1";
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
        ), array(
            'auth' => false
        ))->json();
        $this->access_token = $res['access_token'];
        if (isset($res['refresh_token'])) $this->refresh_token = $res['refresh_token'];
    }

    public function get($path, $options = array())
    {
        return $this->response_to_model($this->request("GET", $path, null, $options));
    }

    public function post($path, $data, $options = array())
    {
        return $this->response_to_model($this->request("POST", $path, $data, $options));
    }

    public function put($path, $data, $options = array())
    {
        return $this->response_to_model($this->request("PUT", $path, $data, $options));
    }

    public function delete($path, $options = array())
    {
        return $this->response_to_model($this->request("DELETE", $path, null, $options));
    }

    public function file($filename, $options = array())
    {
        return $this->request("GET", "content/upload/{$filename}", null, $options)->getBody();
    }

    public function upload($file, $options = array())
    {
        if (!is_resource($file)) {
            throw new \Exception('FridgeApi::upload. File must be a stream.');
        }
        return $this->request("POST", "content/upload", array('file' => $file), $options)->json();
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

    public function request($method, $path, $data=null, $options=null)
    {
        $req = $this->agent->createRequest($method, $this->base."/".$path);
        if ($method == "POST" || $method == "PUT") {
            $req = $this->agent->createRequest($method, $this->base."/".$path, array(
                'body' => $data
            ));
        }

        // use Authentication by default
        $auth = true;
        $debug = $this->debug;

        if ($options) {
            // parse options
            if (isset($options['auth'])) {
                $auth = $options['auth'];
                unset($options['auth']);
            }
            if (isset($options['debug'])) {
                $debug = $options['debug'];
                unset($options['debug']);
            }

            // use other options as query string
            $query = $req->getQuery();
            foreach ($options as $q => $v) $query[$q] = $v;
        }

        if ($auth) {
            if (!$this->access_token) $this->authenticate();
            $req->setHeader('Authorization', 'token ' . $this->access_token);
        }

        if ($debug) error_log($req->__toString());

        try {
            return $this->agent->send($req);
        } catch(\Exception $e) {
            if ($this->debug) error_log("Fridge API Exception -- " . $e->getMessage());
            return false;
        }
    }
}
