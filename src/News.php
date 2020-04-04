<?php

namespace Fortnite;

use Fortnite\Language;
use Fortnite\FortniteClient;

use Fortnite\Model\FortniteNews;

use GuzzleHttp\Exception\GuzzleException;

class News
{
    // TODO: Add more types
    const BATTLEROYALE = "battleroyalenews";
    const SAVETHEWORLD = "savetheworldnews";

    private $access_token;

    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    public function get($type, $lang = Language::ENGLISH)
    {
        // TODO: do something with that
        if (
            $lang !== Language::ENGLISH
            && $lang !== Language::GERMAN
            && $lang !== Language::SPANISH
            && $lang !== Language::CHINESE
            && $lang !== Language::FRENCH
            && $lang !== Language::FRENCH
            && $lang !== Language::ITALIAN
            && $lang !== Language::JAPANESE
        )
            throw new \Exception("Unknown Language");

        if ($type != Self::SAVETHEWORLD && $type != Self::BATTLEROYALE)
            throw new \Exception("Only SaveTheWorld and BattleRoyale news are currently supported");

        try {
            $data = FortniteClient::sendFortniteGetRequest(
                FortniteClient::FORTNITE_NEWS_API . 'pages/fortnite-game',
                $this->access_token,
                ['Accept-Language' => $lang]
            );

            $data = $data->$type->news->messages;

            $news = [];
            foreach ($data as $key => $stat) {
                $news[$key] = new FortniteNews($stat);
            }

            return $news;
        } catch (GuzzleException $e) {
            if ($e->getCode() == 404) throw new \Exception('Unable to obtain news.');
            throw $e;
        }
    }
}
