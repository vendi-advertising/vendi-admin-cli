<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Vendi\Shared\fs_utils;

class _bash_installer_with_php_base extends _bash_installer_base
{
    const PHP_VERSION = '7.1';

    final protected function is_php_installed()
    {
        $io = $this->get_or_create_io();

        $command = 'type php && php -v';

        if( ! $this->_run_command( $command, '', true ) )
        {
            return false;
        }

        return true;
    }
}
