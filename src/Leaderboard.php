<?php

namespace Fortnite;

use Fortnite\Mode;
use Fortnite\Platform;
use Fortnite\FortniteClient;

use Fortnite\Model\FortniteLeaderboard;


use GuzzleHttp\Exception\GuzzleException;
use Fortnite\Exception\UserNotFoundException;

use Fortnite\Exception\StatsNotFoundException;
use Fortnite\Exception\LeaderboardNotFoundException;

class Leaderboard
{
    private $access_token;
    private $in_app_id;
    private $account;


    public function __construct($access_token, $in_app_id, Account $account)
    {
        $this->account = $account;
        $this->in_app_id = $in_app_id;
        $this->access_token = $access_token;
    }

    /**
     * Get leaderboard
     * @param  string   $platform (KEYBOARDMOUSE, GAMEPAD, TOUCH)
     * @param  string   $type (SOLO, DUO, SQUAD)
     * @param  int      $limit Max = 1000
     * @return object   New instance of Fortnite\Leaderboard
     */
    public function get($platform, $type, $limit = 10)
    {
        if (
            $platform !== Platform::GAMEPAD
            && $platform !== Platform::TOUCH
            && $platform !== Platform::KEYBOARDMOUSE
        )
            throw new \Exception('Please select a platform');

        if (
            !in_array($type, Mode::DUO_MODES)
            && !in_array($type, Mode::SOLO_MODES)
            && !in_array($type, Mode::SQUAD_MODES)
        ) {
            throw new \Exception('Please select a game mode');
        }

        try {
            /* Request format: br_placetop1_{platform}_m0_playlist_{type}
             * Only placetop1 or wins is available, which are actually the same
            */
            $data_cohort = FortniteClient::sendFortniteGetRequest(
                FortniteClient::FORTNITE_LEADERBOARD_API . "br_placetop1_{$platform}_m0_playlist_{$type}",
                $this->access_token
            );
            $entries = $data_cohort->entries;
            $entries = array_slice($entries, 0, $limit);

            $ids = array();
            foreach ($entries as $key => $entry) {
                if ($key >= $limit) {
                    break;
                }

                $entry->account = str_replace("-", "", $entry->account);
                array_push($ids, $entry->account);
            }

            // Avoid restriction of 2048 symbols in GET request
            $accounts = [];
            $chunked_ids = array_chunk($ids, 5);
            foreach ($chunked_ids as $chunked_ids_portion) {
                $accounts_portion = (array) $this->account->getDisplayNamesFromID($chunked_ids_portion);
                $accounts = array_merge($accounts, $accounts_portion);
            }

            foreach ($entries as $entry) {
                foreach ($accounts as $account) {
                    if ($entry->account === $account->id) {
                        $entry->displayName = $account->displayName ?? null;
                        break;
                    }
                }
            }

            $leaderboard = [];
            foreach ($entries as $key => $stat) {
                $leaderboard[$key] = new FortniteLeaderboard($stat);
            }

            return $leaderboard;
        } catch (GuzzleException $e) {
            if ($e->getCode() == 404) {
                throw new LeaderboardNotFoundException('Could not get leaderboards.');
            }
            throw $e;
        }
    }
}
