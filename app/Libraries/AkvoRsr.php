<?php

namespace App\Libraries;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class AkvoRsr
{
    public function __construct()
    {
        $this->token = config('akvo-rsr.token');
    }

    public function getHeaders()
    {
        return [
            'content-type' => 'application/json',
            'Authorization' => $this->token,
        ];
    }

    public function get($endpoint, $param = false, $value = false, $limit = 100)
    {
        $queryParams = [
            'format' => 'json',
            'limit'  => $limit,
        ];

        if ($param && $value) {
            $queryParams[$param] = $value;
        }

        $baseUrl = rtrim(config('akvo-rsr.endpoints.' . $endpoint), '/');
        $url = $baseUrl . '?' . http_build_query($queryParams);
        return $this->fetch($url);
    }

    public function fetch($url)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->get($url, [
                'headers' => $this->getHeaders()
            ]);

            if ($response->getStatusCode() === 200) {
                $body = (string) $response->getBody();
                return json_decode($body, true);
            }

            return $response->getStatusCode();

        } catch (RequestException $e) {
            $response = $e->hasResponse() ? $e->getResponse() : null;
            return $response;
        }
    }
}
