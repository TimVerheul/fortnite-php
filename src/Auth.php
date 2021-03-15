<?php

namespace Fortnite;

use Fortnite\Stats;
use Fortnite\Status;
use Fortnite\Profile;
use GuzzleHttp\Client;
use Fortnite\FortniteClient;
use Fortnite\Exception\TwoFactorAuthRequiredException;

class Auth
{
    private $access_token;
    private $in_app_id;
    private $refresh_token;
    private $account_id;
    private $expires_in;
    private $refresh_expires_in;

    public $profile;

    /**
     * Constructs a new Fortnite\Auth instance.
     * @param string $access_token  OAuth2 access token
     * @param string $refresh_token OAuth2 refresh token
     * @param string $account_id    Unreal Engine account id
     * @param string $expires_in    OAuth2 token expiration time
     */
    private function __construct($access_token, $in_app_id, $refresh_token, $account_id, $expires_in, $refresh_expires_in)
    {
        $this->access_token = $access_token;
        $this->in_app_id = $in_app_id;
        $this->refresh_token = $refresh_token;
        $this->account_id = $account_id;
        $this->expires_in = $expires_in;
        $this->refresh_expires_in = $refresh_expires_in;

        $this->account = new Account($this->access_token, $this->account_id);
        $this->status = new Status($this->access_token);
        $this->stats = new Stats($this->access_token);

        if ($this->status->allowedToPlay() === false) {
            $this->account->acceptEULA();
        }

        /**
         * If you need the profile, leaderboard, store etc. just to run on
         */
        $this->profile = new Profile($this->access_token, $this->account_id);
        $this->leaderboard  = new Leaderboard($this->access_token, $this->in_app_id, $this->account);
        $this->store = new Store($this->access_token);
        $this->news = new News($this->access_token);
    }

    /**
     * Login using Unreal Engine credentials to access Fortnite API.
     *
     * @param      string     $email     The account email
     * @param      string     $password  The account password
     *
     * @throws     Exception  Throws exception on API response errors (might get overridden by Guzzle exceptions)
     *
     * @return     self       New Auth instance
     */
    public static function login($email, $password, $code, $account_id = '', $secret = '')
    {
        // Only needed in some cases
        $requestParams = [
            'token_type' => 'eg1'
        ];

        // Checks if the deviceAuth method is available
        if (file_exists(dirname(__FILE__) . '/deviceAuth.json')) {
            $deviceAuthJson = file_get_contents(dirname(__FILE__) . '/deviceAuth.json');
            $deviceAuth = json_decode($deviceAuthJson);
            $device_id = $deviceAuth->deviceId;
            $account_id = $deviceAuth->accountId;
            $secret = $deviceAuth->secret;
        }

        if (!empty($account_id) && !empty($secret)) {
            // Device auth. Preferred method to authenticate.
            return SELF::deviceAuth($requestParams, $device_id, $account_id, $secret);
        } elseif (!empty($code)) {
            // Exchange auth. You can use this to set the DeviceAuth credentials.
            return SELF::exchangeAuth($requestParams, $code);
        } elseif (!empty($email) && !empty($password)) {
            // Email and Password login. Probably will get captcha error. DEPRECATED
            return SELF::EmailandPasswordAuth($requestParams, $email, $password);
        }
    }

    /**
     * Refreshes OAuth2 tokens using an existing refresh token.
     * @param  string $refresh_token Existing OAuth2 refresh token
     * @return self                New Auth instance
     */
    public static function refresh($refresh_token)
    {
        $client = new Client(['cookies' => true]);

        $data = FortniteClient::sendUnrealClientPostRequest($client, FortniteClient::EPIC_OAUTH_TOKEN_ENDPOINT, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'token_type' => 'eg1'
        ], FortniteClient::FORTNITE_AUTHORIZATION);

        if (!$data->access_token) {
            throw new \Exception($data->errorMessage);
        }

        return new self(
            $data->access_token,
            $data->in_app_id,
            $data->refresh_token,
            $data->account_id,
            $data->expires_in,
            $data->refresh_expires
        );
    }

    /**
     * Auth with device tokens.
     * @param array $requestParams  Existing request parameters
     * @param string $device_id     Existing device_id
     * @param string $account_id    Existing account_id
     * @param string $secret        Existing secret
     * @return self                 New Auth instance
     */
    public static function deviceAuth($requestParams, $device_id, $account_id, $secret)
    {
        $requestParams = array_merge($requestParams, [
            'grant_type' => 'device_auth',
            'device_id' => $device_id,
            'account_id' => $account_id,
            'secret' => $secret
        ]);
        $client = new Client(['cookies' => true]);

        $dataToken = FortniteClient::sendUnrealXSRFClientPostRequest($client);

        $data = FortniteClient::deviceAuth($client, FortniteClient::DEVICE_AUTH_LOGIN, $requestParams, $dataToken);

        // Device auth credentials are not valid.
        if (isset($data->errorCode) && $data->errorCode === 'errors.com.epicgames.account.invalid_account_credentials') {
            exit('invalid account credentials');
        }

        if (!isset($data->access_token)) {
            throw new \Exception($data->errorMessage);
        }

        return new self(
            $data->access_token,
            $data->in_app_id,
            $data->refresh_token,
            $data->account_id,
            $data->expires_in,
            $data->refresh_expires
        );
    }

    /**
     * Auth with exchange token.
     * @param array $requestParams  Existing request parameters
     * @param string $code Existing exchange token
     * @return self                 New Auth instance
     */
    public static function exchangeAuth($requestParams, $code)
    {
        $requestParams = array_merge($requestParams, [
            'grant_type' => 'exchange_code',
            'exchange_code' => $code,
            'includePerms' => true
        ]);

        $client = new Client(['cookies' => true]);

        $dataToken = FortniteClient::sendUnrealXSRFClientPostRequest($client);

        $data = FortniteClient::deviceAuth($client, FortniteClient::EPIC_OAUTH_TOKEN_ENDPOINT, $requestParams, $dataToken);

        // Exchange code not valid. Read here how to generate one:
        // https://github.com/xMistt/fortnitepy-bot/wiki/Exchange-Code
        if (isset($data->errorCode) && $data->errorCode === 'errors.com.epicgames.account.oauth.exchange_code_not_found') {
            exit('invalid exchange code');
        }

        if (!isset($data->access_token)) {
            throw new \Exception($data->errorMessage);
        }

        $tokens = [
            'access_token' => $data->access_token,
            'in_app_id' => $data->in_app_id,
            'refresh_token' => $data->refresh_token,
            'account_id' => $data->account_id,
            'expires_in' => $data->expires_in,
            'refresh_expires' => $data->refresh_expires
        ];

        $data = FortniteClient::account_generate_device_auth($client, $data->account_id, $data->access_token);

        // Saves the deviceAuth variables in a .json file so we can use the DeviceAuth method.
        Auth::saveDeviceAuth($data->deviceId, $data->accountId, $data->secret);

        return new self(
            $tokens['access_token'],
            $tokens['in_app_id'],
            $tokens['refresh_token'],
            $tokens['account_id'],
            $tokens['expires_in'],
            $tokens['refresh_expires']
        );
    }

    /**
     * Auth with email and password token.
     * @param string $email        Email to auth with
     * @param string $password     Password to auth with
     * @param array $requestParams Existing request parameters
     * @return self                New Auth instance
     */
    public static function EmailandPasswordAuth($requestParams, $email, $password)
    {

        $client = new Client(['cookies' => true]);

        $dataToken = FortniteClient::sendUnrealXSRFClientPostRequest($client);

        try {
            $data = FortniteClient::sendUnrealClientLoginRequestPostRequest($client, $dataToken, $email, $password);
        } catch (\Exception $e) {
            $dataToken = FortniteClient::sendUnrealXSRFClientPostRequest($client);
            $data = FortniteClient::sendUnrealClientLoginRequestPostRequest($client, $dataToken, $email, $password);
        }

        // Got a captcha error. Use exchangeAuth to login or use exchangeAuth to set the DeviceAuth credentials.
        if (isset($data->errorCode) && $data->errorCode === 'errors.com.epicgames.common.two_factor_authentication.required') {
            exit('captcha error');
        }

        $dataParam = FortniteClient::sendUnrealClientExchangePostRequest($client, $dataToken);

        $requestParams = array_merge($requestParams, [
            'grant_type' => 'exchange_code',
            'exchange_code' => $dataParam->code,
            'username' => $email,
            'password' => $password
        ]);

        // First, we need to get a token for the Unreal Engine client
        $data = FortniteClient::sendUnrealClientPostRequest($client, FortniteClient::EPIC_OAUTH_TOKEN_ENDPOINT, $requestParams);

        if (!isset($data->access_token)) {
            if ($data->errorCode === 'errors.com.epicgames.common.two_factor_authentication.required') {
                throw new TwoFactorAuthRequiredException($data->challenge);
            }

            throw new \Exception($data->errorMessage);
        }

        // Now that we've got our Unreal Client launcher token, let's get an exchange token for Fortnite
        $data = FortniteClient::sendUnrealClientGetRequest($client, FortniteClient::EPIC_OAUTH_EXCHANGE_ENDPOINT, $data->access_token, true);

        if (!isset($data->code)) {
            throw new \Exception($data->errorMessage);
        }

        // Should be good. Let's get our tokens for the Fortnite API
        $data = FortniteClient::sendUnrealClientPostRequest($client, FortniteClient::EPIC_OAUTH_TOKEN_ENDPOINT, [
            'grant_type' => 'exchange_code',
            'exchange_code' => $data->code,
            'token_type' => 'eg1'
        ], FortniteClient::FORTNITE_AUTHORIZATION);

        if (!isset($data->access_token)) {
            throw new \Exception($data->errorMessage);
        }

        return new self(
            $data->access_token,
            $data->in_app_id,
            $data->refresh_token,
            $data->account_id,
            $data->expires_in,
            $data->refresh_expires
        );
    }

    /**
     * Return if there is a valid access token
     *
     */
    public static function access($token, $refresh_token, $account_id)
    {
        return new self(
            $token,
            null,
            $refresh_token,
            $account_id,
            21600,
            86400
        );
    }

    /**
     * Returns current refresh token.
     * @return string OAuth2 refresh token
     */
    public function refreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * Returns the time until the OAuth2 access token expires.
     * @return string Time until OAuth2 access token expires (in seconds)
     */
    public function expiresIn()
    {
        return $this->expires_in;
    }

    /**
     * Returns the time until the OAuth2 refresh token expires.
     * @return string Time until Oauth2 refresh token expires 
     */
    public function refreshExpiresIn()
    {
        return $this->refresh_expires_in;
    }

    /**
     * Returns current access token.
     * @return string OAuth2 access token
     */
    public function accessToken()
    {
        return $this->access_token;
    }


    /**
     * Returns current in app id.
     * @return string InAppId access token
     */
    public function inAppId()
    {
        return $this->in_app_id;
    }


    public static function saveDeviceAuth($device_id, $account_id, $secret)
    {
        $data = [
            'deviceId' => $device_id,
            'accountId' => $account_id,
            'secret' => $secret
        ];
        $fp = fopen(dirname(__FILE__) . '/deviceAuth.json', 'w');
        fwrite($fp, json_encode($data));
        fclose($fp);
    }
}
