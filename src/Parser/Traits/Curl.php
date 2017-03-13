<?php
namespace IvanCLI\Chargify\Traits;
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 23/10/2016
 * Time: 2:55 PM
 */
trait Curl
{
    public function _get($url, $headers = array())
    {
        $options = array(
            "headers" => $headers,
        );
        $response = $this->__sendCurl($url, $options);
        return json_decode($response);
    }

    public function _post($url, $data = null, $headers = array())
    {
        $options = array(
            "headers" => $headers,
            "method" => "post",
            "fields" => is_null($data) ? null : json_encode($data),
            "data_type" => "json",
        );
        $response = $this->__sendCurl($url, $options);
        return json_decode($response);
    }

    public function _put($url, $data = null, $headers = array())
    {
        $options = array(
            "headers" => $headers,
            "method" => "put",
            "fields" => is_null($data) ? null : json_encode($data),
            "data_type" => "json",
        );
        $response = $this->__sendCurl($url, $options);
        return json_decode($response);
    }

    public function _delete($url, $data = null, $headers = array())
    {
        $options = array(
            "headers" => $headers,
            "method" => "delete",
            "fields" => is_null($data) ? null : json_encode($data),
            "data_type" => "json",
            "show_header" => 1
        );
        $response = $this->__sendCurl($url, $options);
        return json_decode($response);
    }


    private function __sendCurl($url, $options)
    {
        $ch = curl_init();
        $curlHeaders = array(
            'Accept-Language: en-us',
            'User-Agent: Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36',
            'Connection: Keep-Alive',
            'Cache-Control: no-cache',
        );

        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach ($options['headers'] as $header) {
                $curlHeaders[] = $header;
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!is_null($options['userpass']) && is_string($options['userpass'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $options['userpass']);
        }
        if (isset($options['method'])) {
            switch ($options['method']) {
                case "post":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    break;
                case "put":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    break;
                case "delete":
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    break;
                case "get":
                default:
            }
        }
        if (isset($options['fields']) && !is_null($options['fields'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['fields']);
            if (isset($options['data_type']) && $options['data_type'] == "json") {
                $curlHeaders[] = 'Content-Type: application/json';
                $curlHeaders[] = 'Content-Length: ' . strlen($options['fields']);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_HEADER, isset($options['show_header']) && $options['show_header'] == 1 ? 1 : 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        /*disable this before push to live*/
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $buffer = curl_exec($ch);
        curl_close($ch);

        unset($ch);
        return $buffer;
    }
}