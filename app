#!/usr/bin/env php
<?php

define( 'VENDI_CLI_FILE', __FILE__ );
define( 'VENDI_CLI_PATH', dirname( __FILE__ ) );

require VENDI_CLI_PATH . '/includes/autoload.php';

use Vendi\CLI;
use Symfony\Component\Console\Application;

$create_site_command = new Vendi\CLI\CreateSiteCommand();

$application = new Application( 'Vendi Admin CLI', '0.1-dev' );
$application->add( $create_site_command );
$application->setDefaultCommand( $create_site_command->getName() );
$application->run();
