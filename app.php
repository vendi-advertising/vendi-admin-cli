<?php

define( 'VENDI_CLI_FILE', __FILE__ );
define( 'VENDI_CLI_PATH', dirname( __FILE__ ) );

require VENDI_CLI_PATH . '/includes/autoload.php';

// $client = new Raven_Client('https://d3e0fec781a84dc28a59ad61d05c685a:d1d0b54ff2804a4e91ec42fa07638548@sentry.io/186243');
// $error_handler = new Raven_ErrorHandler( $client );
// $error_handler->registerExceptionHandler();
// $error_handler->registerErrorHandler();
// $error_handler->registerShutdownFunction();

$create_site_wizard = new Vendi\CLI\Commands\create_site_wizard();

$application = new Symfony\Component\Console\Application( 'Vendi Admin CLI', '0.1-dev' );
$application->add( $create_site_wizard );
$application->add( new Vendi\CLI\Commands\create_file_system_command() );
$application->add( new Vendi\CLI\Commands\create_database_command() );
$application->add( new Vendi\CLI\Commands\cms_download_command() );
$application->add( new Vendi\CLI\Commands\configure_nginx_command() );
$application->add( new Vendi\CLI\Commands\init_command() );
$application->add( new Vendi\CLI\Commands\redis_installer() );
$application->add( new Vendi\CLI\Commands\nginx_installer() );
$application->add( new Vendi\CLI\Commands\mariadb_installer() );
$application->add( new Vendi\CLI\Commands\phpredis_installer() );
$application->add( new Vendi\CLI\Commands\php_installer() );
$application->add( new Vendi\CLI\Commands\libsodium_installer() );

$application->setDefaultCommand( $create_site_wizard->getName() );
$application->run();


