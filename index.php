<?php

use Symfony\Component\Console\Application;
use William\Uscis\Commands\AddQuestion;
use William\Uscis\Commands\Ask;
use William\Uscis\Commands\Quiz;

require 'vendor/autoload.php';

$app = new Application('USCIS Quiz');
$app->add(new AddQuestion());
$app->add(new Ask());
$app->add(new Quiz());
$app->run();