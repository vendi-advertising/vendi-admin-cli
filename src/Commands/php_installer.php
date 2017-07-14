<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class php_installer extends _bash_installer_with_php_base
{

    protected function configure()
    {
        $this
            ->setName( 'install-php' )
            ->setDescription( 'Install PHP' )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {

    }

    protected function test_install( bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $expected_php_version = self::PHP_VERSION;

        $command = "type php && php --version | grep \"PHP $expected_php_version\"";

        if( ! $this->_run_command( $command, 'An unknown error occurred while attempting to test PHP', $quiet ) )
        {
            return false;
        }

        if( ! $quiet )
        {
            $io->success( "PHP installed and tested" );
        }

        return true;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = $this->get_or_create_io( $input, $output );

        $is_root = ( 0 === posix_getuid() );

        if( ! $is_root )
        {
            $io->error( 'This command needs to run as root. Please re-run it with sudo privileges.' );
            exit;
        }

        $expected_php_version = self::PHP_VERSION;

        if( $this->test_install( true ) )
        {
            $io->success( 'PHP already installed, skipping' );
        }
        else
        {
            $this->add_ppa( 'ppa:ondrej/php' );
            $this->update_apt_get();

            $this->install_package( "php-common php${expected_php_version}-common php${expected_php_version}-json php${expected_php_version}-opcache php${expected_php_version}-readline php${expected_php_version} php${expected_php_version}-mcrypt php${expected_php_version}-curl php${expected_php_version}-cli php${expected_php_version}-mysql php${expected_php_version}-gd php${expected_php_version}-intl php${expected_php_version}-fpm php${expected_php_version}-mbstring" );

            $this->echo_to_file( "\n" . 'cgi.fix_pathinfo=0' . "\n", "/etc/php/${expected_php_version}/fpm/conf.d/20-vendi.ini" );
            $this->echo_to_file( "\n" . 'cgi.fix_pathinfo=0' . "\n", "/etc/php/${expected_php_version}/cli/conf.d/20-vendi.ini" );

            $this->restart_service( "php${expected_php_version}-fpm" );

            $this->test_install();

            $io->success( "PHP installed and tested" );
        }
    }
}
