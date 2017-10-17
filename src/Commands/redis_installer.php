<?php

namespace Vendi\CLI\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

use Webmozart\PathUtil\Path;

class redis_installer extends _bash_installer_base
{

    protected function configure()
    {
        $this
            ->setName( 'install-redis' )
            ->setDescription( 'Install Redis' )
            ->setHidden( true )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {

    }

    protected function test_install( bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "redis-benchmark -q -n 1000 -c 10 -P 5";

        $this->_run_command( $command, 'An unknown error occurred while attempting to test the redis server', $quiet  );

        if( ! $quiet )
        {
            $io->success( "Redis installed and tested" );
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

        if( $this->test_install( true ) )
        {
            $io->success( 'Redis already installed, skipping' );
        }
        else
        {
            $this->add_ppa( 'ppa:chris-lea/redis-server' );
            $this->update_apt_get();
            $this->install_package( 'redis-server' );
            $this->test_install();
        }
    }
}
