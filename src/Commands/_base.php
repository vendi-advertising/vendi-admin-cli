<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption( 'cms-type-wordpress',  null, InputOption::VALUE_NONE,      'Use the WordPress file system.' )
            ->addOption( 'cms-type-drupal',     null, InputOption::VALUE_NONE,      'Use the Drupal file system.' )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        $this->_is_wordpress       = $input->getOption( 'cms-type-wordpress' );
        $this->_is_drupal          = $input->getOption( 'cms-type-drupal' );

        if( $this->_is_wordpress && $this->_is_drupal )
        {
            throw new \Exception( 'We do not support websites being both Drupal and WordPress currently.' );
        }

        if( ! $this->_is_wordpress && ! $this->_is_drupal )
        {
            throw new \Exception( 'You must pick either a WordPress or Drupal site.' );
        }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        throw new \Exception( 'Child classes must handle this themselves.' );
    }


}
