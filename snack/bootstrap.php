<?php

use Snack\Snack;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require_once dirname(dirname(__FILE__)) . '/config.php';

$snack = Snack::getInstance()
    ->setOrm(new $config['orm'])
    ->setRouter(new $config['router']);;
