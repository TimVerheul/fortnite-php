<?php

namespace Fortnite;

use Fortnite\Model\FortniteStats;
use Fortnite\Exception\InvalidGameModeException;

class Platform
{
    public const GAMEPAD = "gamepad";
    public const TOUCH = "touch";
    public const KEYBOARDMOUSE = "keyboardmouse";

    public $solo;
    public $duo;
    public $squad;

    public function __construct($platform)
    {
        foreach ($platform as $key => $mode) {
            switch ($key) {
                case "solo":
                    $this->solo = new FortniteStats($mode);
                    break;
                case "squad":
                    $this->squad = new FortniteStats($mode);
                    break;
                case "duo":
                    $this->duo = new FortniteStats($mode);
                    break;
                default:
                    throw new InvalidGameModeException('Mode ' . $key . ' is invalid.'); // Will be thrown if a new game mode is added
            }
        }
    }
}
