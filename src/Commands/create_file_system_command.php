<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class create_file_system_command extends _base_with_fs
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName( 'create-file-system' )
            ->setHidden( true )
            ->setDescription( 'Create the file system for a site' )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $base = rtrim( $this->_file_system_root, '/' ) . '/' . $this->_folder_name;

        $cms_folder = $this->_is_wordpress ? 'wp-site' : 'drupal-site';

        $directories = [
                            $base,
                            $base . '/' . $this->_stage_type,
                            $base . '/' . $this->_stage_type . '/' . 'logs',
                            $base . '/' . $this->_stage_type . '/' . 'nginx',
                            $base . '/' . $this->_stage_type . '/' . $cms_folder,
        ];

        $io = $this->get_or_create_io( $input, $output);

        foreach( $directories as $dir )
        {
            if( is_dir( $dir ) )
            {
                $io->success( sprintf( 'Directory %1$s already exists... skipping', $dir ) );
                continue;
            }

            $result = @mkdir( $dir );
            if( ! is_dir( $dir ) )
            {
                $io->error( sprintf( 'Unable to create directory %1$s... exiting', $dir ) );
                exit;
            }

            $io->success( sprintf( 'Created directory %1$s', $dir ) );
        }
    }


}

