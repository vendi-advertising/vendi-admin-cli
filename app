#!/usr/bin/env php
<?php

require __DIR__ . '/autoload.php';

use Vendi\CLI;
use Symfony\Component\Console\Application;

$application = new Application('Cliph', '0.1-dev');
$application->add( new Vendi\CLI\HelloCommand() );
$application->add( new Vendi\CLI\CreateSiteCommand() );
$application->run();
