<?php

namespace Fortnite;

use Fortnite\FortniteClient;

use GuzzleHttp\Exception\GuzzleException;

class Store
{
    private $access_token;

    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    public function get()
    {

        try {
            $data = FortniteClient::sendFortniteGetRequest(
                FortniteClient::FORTNITE_API . 'storefront/v2/catalog',
                $this->access_token
            );
            return $data;
        } catch (GuzzleException $e) {
            if ($e->getCode() == 404) throw new \Exception('Unable to obtain store info.');
            throw $e;
        }
    }
}
