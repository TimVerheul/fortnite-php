<?php

require_once 'vendor/autoload.php';

use Fortnite\Auth;

$auth = Auth::login('email@email.email', 'password', 'putyourexchangecodehere');

$sandy = $auth->stats->lookup('sandalzrevenge');
var_dump($sandy);

/**
 * Try out your own way to get the data, or use my bot ;)
 * https://fortnite.tchverheul.nl/
 */
