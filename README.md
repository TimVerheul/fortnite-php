# Fortnite-PHP Wrapper
Interact with the official Fortnite API using PHP.
As the original fortnite-php project isn't maintained anymore, you can use this one. I will do my best to actively maintain it.

[![Packagist](https://img.shields.io/packagist/l/doctrine/orm.svg)]()
[![Packagist](https://img.shields.io/packagist/v/Tustin/fortnite-php.svg)]()

## Installation
Pull in the project using composer:
`composer require Tustin/fortnite-php`

Or if you want to use this fork:
`composer require Tustin/fortnite-php`
Edit your composer.json and add this line before the last `},`
```json
"repositories": [{
        "type": "vcs",
        "url": "https://github.com/TimVerheul/fortnite-php"
    }]
```

## Usage
Create a basic test script to ensure everything was installed properly
```php
<?php

require_once 'vendor/autoload.php';

use Fortnite\Auth;
use Fortnite\Account;
use Fortnite\Mode;
use Fortnite\Language;
use Fortnite\Platform;

// Authenticate
$auth = Auth::login('epic_email@domain.com','password', 'putyourexchangecodehere');

// Output each stat for all applicable platforms
var_dump($auth->stats);

// Grab someone's stats
$sandy = $auth->stats->lookup('sandalzrevenge');
echo 'Sandy Ravage has won ' . $sandy->keyboardmouse->solo->wins . ' solo games and ' . $sandy->keyboardmouse->squad->wins . ' squad games!';
```

### Get Leaderboards
```php
$auth = Auth::login('epic_email@domain.com','password', 'putyourexchangecodehere');
var_dump($auth->leaderboard->get(Platform::KEYBOARDMOUSE, Mode::DUO)); 
```

### Get News 
```php
$auth = Auth::login('epic_email@domain.com','password', 'putyourexchangecodehere');
var_dump($auth->news->get(News::BATTLEROYALE, Language::ENGLISH)); 
```

### Get Store
```php
$auth = Auth::login('epic_email@domain.com','password', 'putyourexchangecodehere');
var_dump($auth->store->get()); 
```

### Get Challenges
```php
$auth = Auth::login('epic_email@domain.com','password', 'putyourexchangecodehere');
// All weekly challenges
var_dump($auth->challenges->getWeeklys()); 

// Or just get a specific week (in this example, week 1)
var_dump($auth->challenges->getWeekly(1)); 
```

### Constants
```
Platform [ keyboardmouse, gamepad, touch ]

Mode [
    SOLO = [
        "defaultsolo", "slide_solo", "bots_defaultsolo", "highexplosives_solo", "slide_solo", "deimos_solo_winter", "showdownalt_solo", "sneaky_solo", "flyexplosives_solo", "snipers_solo", "comp_solo", "ww_solo", "bounty_solo", "low_solo", "goose_solo_24", "blitz_solo", "close_solo", "bling_solo"
        ]

    DUO = [
        "defaultduo", "defaultduos", "bots_defaultduo", "bots_defaultduos", "highexplosives_duos", "slide_duos", "demios_duos_winter", "shutdownalt_duos", "sneaky_duos", "flyexplosives_duos", "snipers_duos", "comp_duos", "ww_duos", "bounty_duos", "low_duos", "goose_duos_24", "blitz_duo", "close_duo", "bling_duos"
        ]

    SQUAD = [
        "defaultsquad", "defaultsquads", "bots_defaultsquad", "bots_defaultsquads", "highexplosives_squads", "slide_squads", "deimos_squad_winter", "shutdownalt_squads", "sneaky_quads", "flyexplosives_quads", "snipers_quads", "comp_quads", "ww_quads", "bounty_quads", "low_quads", "goose_quads_24", "blitz_squad", "close_squad", "bling_squads"
        ]
 ]

Language [ en, de, es, zh, fr, it, ja ]

News [ battleroyalenews, savetheworldnews ]
```

## Contributing
Fortnite now utilizes SSL certificate pinning in their Windows client in newer versions. I suggest using the iOS mobile app to do any future API reversing as both cheat protections on the Windows client make it difficult to remove the certificate pinning. If SSL certificate pinning is added to the iOS version, I could easily provide a patch to remove that as the iOS version doesn't contain any anti-cheat.
