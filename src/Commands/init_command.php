<?php

namespace Vendi\CLI\Commands;

use Vendi\CLI\config_info;
use Vendi\CLI\Configs\raven_config;

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

class init_command extends Command
{

    private $_config_file_name = 'vendi-admin-cli.yml';

    protected function configure()
    {
        $this
            ->setName( 'init' )
            ->setDescription( 'Initialize settings for this server' )
            ->setHidden( true )
        ;

        // $this
        //     ->addOption( 'cms-type-wordpress',  null, InputOption::VALUE_NONE,      'Use the WordPress file system.' )
        //     ->addOption( 'cms-type-drupal',     null, InputOption::VALUE_NONE,      'Use the Drupal file system.' )
        // ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {

    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = new SymfonyStyle( $input, $output );

        $is_root = ( 0 === posix_getuid() );

        if( ! $is_root )
        {
            $io->note( 'You are running as non-root so we can only save these changes for your user.' );
            if( $io->confirm( 'Would you like to exit and re-run as root?', true ) )
            {
                exit;
            }
        }

        $config_paths = ( new config_info )->get_config_paths();

        $configs = [];
        foreach( $config_paths as $key => $path )
        {
            $configs[ $key ] = [];

            if( is_file( $path ) )
            {
                $configs[ $key ] = Yaml::parse(  file_get_contents( $path ) );
            }
        }

        $processor = new Processor();
        $configuration = new raven_config();
        $processedConfiguration = $processor->processConfiguration(
            $configuration,
            $configs
        );

        $io->section( 'Sentry.IO config' );

        $text = sprintf( 'The Sentry.IO web hook URL is %1$s set, would you like to change it?', array_key_exists( 'web_hook_url', $processor ) ? 'already' : 'no' );

        $result = $io->confirm( $text, array_key_exists( 'web_hook_url', $processor ) );
    }
}
