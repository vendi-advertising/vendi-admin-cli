<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class _base_with_fs extends _base
{
    protected $_stage_type;
    protected $_file_system_root;
    protected $_folder_name;

    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument( 'top-level-folder-name', InputArgument::REQUIRED, 'The sanitized client/project folder name.' )
            ->addArgument( 'stage-type',            InputArgument::REQUIRED, 'The stage type.' )
            ->addArgument( 'file-system-root',      InputArgument::REQUIRED, 'The absolute path to the root of the web filesystem.' )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        parent::initialize( $input, $output );

        $this->_stage_type         = $input->getArgument( 'stage-type' );
        $this->_file_system_root   = $input->getArgument( 'file-system-root' );
        $this->_folder_name        = $input->getArgument( 'top-level-folder-name' );

        if( ! is_dir( $this->_file_system_root ) )
        {
            throw new \Exception( 'File system root does not exist. I can\'t to everything!!!' );
        }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        throw new \Exception( 'Child classes must handle this themselves.' );
    }


}
