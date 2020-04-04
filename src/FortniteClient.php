<?php

namespace Fortnite;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class FortniteClient
{
    /**
     * New Endpoints
     */
    const CSRF_TOKEN = 'https://www.epicgames.com/id/api/csrf';
    const API_LOGIN = 'https://www.epicgames.com/id/api/login';
    const API_EXCHANGE_CODE = 'https://www.epicgames.com/id/api/exchange';

    /**
     * base64 encoded string of two MD5 hashes delimited by a colon. The two hashes are the client_id and client_secret OAuth2 fields.
     */
    const IOS_TOKEN   = "MzQ0NmNkNzI2OTRjNGE0NDg1ZDgxYjc3YWRiYjIxNDE6OTIwOWQ0YTVlMjVhNDU3ZmI5YjA3NDg5ZDMxM2I0MWE=";
    const EPIC_LAUNCHER_AUTHORIZATION    = "MzRhMDJjZjhmNDQxNGUyOWIxNTkyMTg3NmRhMzZmOWE6ZGFhZmJjY2M3Mzc3NDUwMzlkZmZlNTNkOTRmYzc2Y2Y=";


    /**
     * Same as EPIC_LAUNCHER_AUTHORIZATION
     */
    const FORTNITE_AUTHORIZATION        = "ZWM2ODRiOGM2ODdmNDc5ZmFkZWEzY2IyYWQ4M2Y1YzY6ZTFmMzFjMjExZjI4NDEzMTg2MjYyZDM3YTEzZmM4NGQ=";


    /**
     * Epic API Endpoints
     */
    const DEVICE_AUTH_LOGIN             = "https://account-public-service-prod.ol.epicgames.com/account/api/oauth/token";
    const EPIC_OAUTH_TOKEN_ENDPOINT     = "https://account-public-service-prod03.ol.epicgames.com/account/api/oauth/token";
    const EPIC_OAUTH_EXCHANGE_ENDPOINT  = "https://account-public-service-prod03.ol.epicgames.com/account/api/oauth/exchange";
    const EPIC_OAUTH_VERIFY_ENDPOINT    = "https://account-public-service-prod03.ol.epicgames.com/account/api/oauth/verify";
    const EPIC_FRIENDS_ENDPOINT         = "https://friends-public-service-prod06.ol.epicgames.com/friends/api/public/friends/";

    /**
     * Fortnite API Endpoints
     */
    const FORTNITE_API                  = "https://fortnite-public-service-prod11.ol.epicgames.com/fortnite/api/";
    const FORTNITE_PERSONA_API          = "https://account-public-service-prod.ol.epicgames.com/account/api/";
    const FORTNITE_ACCOUNT_API          = "https://account-public-service-prod03.ol.epicgames.com/account/api/";
    const FORTNITE_NEWS_API             = "https://fortnitecontent-website-prod07.ol.epicgames.com/content/api/";
    const FORTNITE_STATUS_API           = "https://lightswitch-public-service-prod06.ol.epicgames.com/lightswitch/api/";
    const FORTNITE_EULA_API             = "https://eulatracking-public-service-prod-m.ol.epicgames.com/eulatracking/api/";
    const FORTNITE_STATS_API            = "https://statsproxy-public-service-live.ol.epicgames.com/statsproxy/api/statsv2/account/";
    const FORTNITE_LEADERBOARD_API      = "https://statsproxy-public-service-live.ol.epicgames.com/statsproxy/api/statsv2/leaderboards/";


    const UNREAL_CLIENT_USER_AGENT      = "game=UELauncher, engine=UE4, build=7.14.2-4231683+++Portal+Release-Live";
    const FORTNITE_USER_AGENT           = "Fortnite/++Fortnite+Release-7.01-CL-4644078 IOS/11.3.1";


    /**
     * Sends a GET request as the Unreal Engine Client.
     * @param  string  $endpoint      API Endpoint to request
     * @param  string  $authorization Authorization header
     * @param  boolean $oauth         Is $authorization an OAuth2 token
     * @return object                 Decoded JSON response body
     */
    public static function sendUnrealClientGetRequest($client, $endpoint, $authorization = self::EPIC_LAUNCHER_AUTHORIZATION, $oauth = false)
    {
        try {
            $response = $client->get($endpoint, [
                'headers' => [
                    'User-Agent' => self::UNREAL_CLIENT_USER_AGENT,
                    'Authorization' => (!$oauth) ? 'basic ' . $authorization : 'bearer ' . $authorization
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw $e; //Throw exception back up to caller
        }
    }

    public static function sendUnrealXSRFClientPostRequest($client)
    {
        try {
            $response = $client->get(self::CSRF_TOKEN);
            $cookieJar = $client->getConfig('cookies');
            foreach ($cookieJar->toArray() as $item) {
                if ($item['Name'] == "XSRF-TOKEN") {
                    $token = $item['Value'];
                }
            }
            return $token;
        } catch (GuzzleException $e) {
            throw $e; //Throw exception back up to caller
        }
    }

    public static function sendUnrealClientLoginRequestPostRequest($client, $token, $email, $password)
    {
        try {
            $response = $client->post(self::API_LOGIN, [
                'form_params' => [
                    'email' => $email,
                    'password' => $password,
                    'rememberMe' => 'false',
                    'captcha' => ''
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'x-xsrf-token' => $token,
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (RequestException $e) {
            $statusCode = $e->getCode();
            if ($statusCode == 400) {
                return json_decode($e->getResponse()->getBody()->getContents());
            } else {
                throw $e;
            }

            throw $e;
        }
    }

    public static function account_generate_device_auth($client, $account_id, $access_token)
    {
        try {
            $response = $client->post(self::FORTNITE_PERSONA_API . 'public/account/' . $account_id . '/deviceAuth', [
                'headers' => [
                    'User-Agent' => self::UNREAL_CLIENT_USER_AGENT,
                    'Authorization' => 'bearer ' . $access_token
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    public static function sendUnrealClientExchangePostRequest($client, $token)
    {
        try {
            $response = $client->get(self::API_EXCHANGE_CODE, [
                'headers' => [
                    'x-xsrf-token' => $token,
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw $e; //Throw exception back up to caller
        }
    }

    /**
     * Sends a POST request as the Unreal Engine Client.
     * @param  string  $endpoint      API Endpoint to request
     * @param  array   $params        Request parameters, using the name as the array key and value as the array value
     * @param  string  $authorization Authorization header
     * @param  boolean $oauth         Is $authorization an OAuth2 token
     * @return object                 Decoded JSON response body
     */
    public static function sendUnrealClientPostRequest($client, $endpoint, $params, $authorization = self::EPIC_LAUNCHER_AUTHORIZATION, $oauth = false)
    {
        try {
            $response = $client->post($endpoint, [
                'form_params' => $params,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => self::UNREAL_CLIENT_USER_AGENT,
                    'Authorization' => (!$oauth) ? 'basic ' . $authorization : 'bearer ' . $authorization,
                    'X-Epic-Device-ID' => self::generateDeviceId()
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw $e; //Throw exception back up to caller
        }
    }

    public static function deviceAuth($client, $endpoint, $params, $xsrf)
    {
        try {
            $response = $client->post($endpoint, [
                'form_params' => $params,
                'headers' => [
                    'x-xsrf-token' => $xsrf,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'basic ' . SELF::IOS_TOKEN,
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * Sends a GET request as the Fortnite client.
     * @param  string $endpoint     API endpoint to request
     * @param  string $access_token OAuth2 access token
     * @param  array  $extra_headers (optional)
     * @return object               Decoded JSON response body
     */
    public static function sendFortniteGetRequest($endpoint, $access_token, $extra_headers = array())
    {
        $client = new Client();

        $headers = [
            'User-Agent' => self::FORTNITE_USER_AGENT,
            'Authorization' => 'bearer ' . $access_token
        ];

        $headers = array_merge($headers, $extra_headers);
        try {
            $response = $client->get($endpoint, [
                'headers' => $headers
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw $e; //Throw exception back up to caller
        }
    }


    /**
     * Sends a POST request as the Fortnite client.
     * @param  string $endpoint     API endpoint to request
     * @param  string $access_token OAuth2 access token
     * @param  array  $params       Request parameters, using the name as the array key and value as the array value
     * @return object               Decoded JSON response body
     */
    public static function sendFortnitePostRequest($endpoint, $access_token, $params = null)
    {
        $client = new Client();

        try {
            $response = $client->post($endpoint, [
                'json' => $params,
                'headers' => [
                    'User-Agent' => self::FORTNITE_USER_AGENT,
                    'Authorization' => 'bearer ' . $access_token
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw $e; //Throw exception back up to caller
        }
    }

    public static function sendFortniteDeleteRequest($endpoint, $access_token, $params = null)
    {
        $client = new Client();

        try {
            $response = $client->delete($endpoint, [
                'json' => $params,
                'headers' => [
                    'User-Agent' => self::FORTNITE_USER_AGENT,
                    'Authorization' => 'bearer ' . $access_token
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw $e; //Throw exception back up to caller
        }
    }

    private static function generateSequence($length)
    {
        return strtoupper((bin2hex(random_bytes($length / 2))));
    }

    public static function generateDeviceId()
    {
        return sprintf(
            '%s-%s-%s-%s-%s',
            self::generateSequence(8),
            self::generateSequence(4),
            self::generateSequence(4),
            self::generateSequence(4),
            self::generateSequence(12)
        );
    }
}
