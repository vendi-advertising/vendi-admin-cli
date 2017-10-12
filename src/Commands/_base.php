<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class _base extends Command
{

    protected $_is_wordpress;
    protected $_is_drupal;

    private $_io;

    public function set_io( SymfonyStyle $io )
    {
        $this->_io = $io;
    }

    protected function get_or_create_io( InputInterface $input, OutputInterface $output ) : SymfonyStyle
    {
        if( ! $this->_io )
        {
            $this->_io = new SymfonyStyle( $input, $output );
        }
        return $this->_io;
    }

    protected function configure()
    {
        $this
            ->addArgument( 'cms-type', InputArgument::REQUIRED, 'The type of CMS to use, either WordPress or Drupal' )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        $cms_type = strtolower( $input->getArgument( 'cms-type' ) );

        switch( $cms_type )
        {
            case 'wordpress':
            case 'wp':
                $this->_is_wordpress = true;
                $this->_is_drupal = false;
                break;

            case 'drupal':
                $this->_is_wordpress = false;
                $this->_is_drupal = true;
                break;

            default:
                throw new \Exception( 'You must pick either a WordPress or Drupal site.' );
        }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        throw new \Exception( 'Child classes must handle this themselves.' );
    }


}
