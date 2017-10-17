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

class copy_site_from_qth_command extends _bash_installer_base
{
    protected function configure()
    {
        $this
            ->setName( 'copy-site-from-qth' )
            ->setDescription( 'Copy site from QTH' )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->write( sprintf( "\033\143" ) );
        $io->title('Copy site from QTH Wizard');

        $this->handle_server_choice( $io );

        $io->error( 'Sorry, I didn\'t get too far on this yet!' );
    }

    private function handle_server_choice( SymfonyStyle $io )
    {
        $server = $io->choice(
                                        'Which server would you like to copy from?',
                                        [
                                            'WWW15',
                                        ]
                                    );
        // $this->_site_info->set_cms_type( $site_type );
    }
}
