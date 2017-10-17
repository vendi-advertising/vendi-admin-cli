<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Webmozart\PathUtil\Path;

class libsodium_installer extends _bash_installer_with_php_base
{

    const LIBSODIUM_VERSION = '1.0.12';

    protected function configure()
    {
        $this
            ->setName( 'install-libsodium' )
            ->setDescription( 'Install PHP Redis' )
            ->setHidden( true )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {

    }

    protected function test_requirements( bool $quiet = false ) : bool
    {
        if( ! $this->is_php_installed() )
        {
            if( $quiet )
            {
                return false;
            }

            $io->error( 'You must install PHP before installing libsodium.' );
            exit;
        }

        return true;
    }

    protected function test_install( bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $expected_version = self::LIBSODIUM_VERSION;

        $command = "type php && php -r 'if( \"${expected_version}\" !== SODIUM_LIBRARY_VERSION ){ exit( 1 ); }'";

        if( ! $this->_run_command( $command, 'An unknown error occurred while attempting to test for libsodium', $quiet ) )
        {
            return false;
        }

        if( ! $quiet )
        {
            $io->success( "Libsodium installed and tested" );
        }

        return true;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = $this->get_or_create_io( $input, $output );

        $is_root = ( 0 === \posix_getuid() );

        if( ! $is_root )
        {
            $io->error( 'This command needs to run as root. Please re-run it with sudo privileges.' );
            exit;
        }

        $expected_version = self::LIBSODIUM_VERSION;

        $expected_php_version = self::PHP_VERSION;

        $this->test_requirements();

        if( $this->test_install( true ) )
        {
            $io->success( 'Libsodium already installed, skipping' );
        }
        else
        {
            $local_folder = $this->_create_tmp_folder();
            $this->install_package( 'build-essential' );
            $this->get_remote_url( "https://download.libsodium.org/libsodium/releases/libsodium-${expected_version}.tar.gz", $local_folder );
            $this->untar_file( "libsodium-${expected_version}.tar.gz", $local_folder );

            $local_folder_sub = Path::join( $local_folder, "libsodium-${expected_version}" );

            $this->set_next_command_timeout( 60 * 5 );
            $this->_run_mulitple_commands_with_working_directory(
                                                                    [
                                                                        './configure',
                                                                        'make',
                                                                        'make check',
                                                                        'make install',
                                                                    ],
                                                                    $local_folder_sub
                                                                );

            $this->remove_directory( $local_folder );

            $this->pecl_install( 'libsodium' );

            $this->echo_to_file( "\n" . 'extension=sodium.so' . "\n", "/etc/php/${expected_php_version}/fpm/conf.d/20-vendi.ini" );
            $this->echo_to_file( "\n" . 'extension=sodium.so' . "\n", "/etc/php/${expected_php_version}/cli/conf.d/20-vendi.ini" );
            $this->restart_service( "php${expected_php_version}-fpm" );

            $this->test_install();

            $io->success( "Libsodium installed and tested" );
        }
    }
}

