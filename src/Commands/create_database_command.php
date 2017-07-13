<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class create_database_command extends _base
{
    private $_database_name;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName( 'create-database' )
            ->setHidden( true )
            ->setDescription( 'Create the database for a site' )
        ;

        $this
            ->addArgument( 'database-name', InputArgument::REQUIRED, 'The sanitized database name.' )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        parent::initialize( $input, $output );
        $this->_database_name = $input->getArgument( 'database-name' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = $this->get_or_create_io( $input, $output);

        $io->note( 'If you are running this on the Helix server just type anything when prompted for a password.' );

        $command = sprintf(
                            'VENDI_RESULT=$(echo "CREATE DATABASE IF NOT EXISTS %1$s; GRANT ALL PRIVILEGES ON %1$s.* TO %1$s@localhost IDENTIFIED BY \'%1$s\'; FLUSH PRIVILEGES; SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \'%1$s\';" | mysql -uroot -p); echo $VENDI_RESULT;',
                            $this->_database_name
        );

        $result = @exec( $command );

        if( 'SCHEMA_NAME ' . $this->_database_name !== $result )
        {
            $io->error( 'Unknown problem while trying to create database... exiting.' );
            exit;
        }

        $io->success( sprintf( 'Created database %1$s', $this->_database_name ) );
    }
}
