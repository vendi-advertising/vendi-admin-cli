<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class config extends Command
{

    private string $_config_file_name = 'vendi-admin-cli.yml';

    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize settings for this server')
            ->setHidden(true);

        // $this
        //     ->addOption( 'cms-type-wordpress',  null, InputOption::VALUE_NONE,      'Use the WordPress file system.' )
        //     ->addOption( 'cms-type-drupal',     null, InputOption::VALUE_NONE,      'Use the Drupal file system.' )
        // ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('Init');

        $is_root = (0 === posix_getuid());

        if (!$is_root) {
            $io->note('You are running as non-root so we can only save these changes for your user.');
            if ($io->confirm('Would you like to exit and re-run as root?', true)) {
                exit;
            }
        }

        $config_paths = (new config_info)->get_config_paths();
    }
}
