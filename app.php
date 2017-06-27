<?php

define( 'VENDI_CLI_FILE', __FILE__ );
define( 'VENDI_CLI_PATH', dirname( __FILE__ ) );

require VENDI_CLI_PATH . '/includes/autoload.php';

use Vendi\CLI;
use Symfony\Component\Console\Application;

$create_site_wizard = new Vendi\CLI\Commands\create_site_wizard();

$application = new Application( 'Vendi Admin CLI', '0.1-dev' );
$application->add( $create_site_wizard );
$application->add( new Vendi\CLI\Commands\create_file_system_command() );
$application->add( new Vendi\CLI\Commands\create_database_command() );
$application->add( new Vendi\CLI\Commands\cms_download_command() );
$application->add( new Vendi\CLI\Commands\configure_nginx_command() );

$application->setDefaultCommand( $create_site_wizard->getName() );
$application->run();
