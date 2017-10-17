<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class phpredis_installer extends _bash_installer_with_php_base
{

    protected function configure()
    {
        $this
            ->setName( 'install-phpredis' )
            ->setDescription( 'Install PHP Redis' )
            ->setHidden( true )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {

    }

    protected function test_install( bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = 'type php && php -r "new Redis();"';

        if( ! $this->_run_command( $command, 'An unknown error occurred while attempting to test PHP Redis', $quiet ) )
        {
            return false;
        }

        if( ! $quiet )
        {
            $io->success( "PHP Redis installed and tested" );
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

        $expected_php_version = self::PHP_VERSION;

        if( ! is_dir( "/etc/php/${expected_php_version}/fpm/" ) )
        {
            $io->error( "PHP ${expected_php_version} does not appear to be installed." );
            exit;
        }

        if( $this->test_install( true ) )
        {
            $io->success( 'PHP Redis already installed, skipping' );
        }
        else
        {
            $local_folder = $this->_create_tmp_folder();
            $this->install_package( "php${expected_php_version}-dev git" );
            $this->clone_git_repo( $local_folder, 'https://github.com/phpredis/phpredis.git' );

            $this->_run_mulitple_commands_with_working_directory(
                                                                    [
                                                                        'git checkout php7',
                                                                        'phpize',
                                                                        './configure',
                                                                        'make',
                                                                        'make install',
                                                                    ],
                                                                    $local_folder
                                                                );

            $this->remove_directory( $local_folder );

            $this->echo_to_file( "\n" . 'extension=redis.so' . "\n", "/etc/php/${expected_php_version}/mods-available/redis.ini" );

            $this->create_symlink( "/etc/php/${expected_php_version}/mods-available/redis.ini", "/etc/php/${expected_php_version}/fpm/conf.d/20-redis.ini", true );
            $this->create_symlink( "/etc/php/${expected_php_version}/mods-available/redis.ini", "/etc/php/${expected_php_version}/cli/conf.d/20-redis.ini", true );
            $this->restart_service( "php${expected_php_version}-fpm" );

            $this->_run_command( "rm -rf ${local_folder}", '' );

            $this->test_install();

            $io->success( "PHP Redis installed and tested" );
        }
    }
}

