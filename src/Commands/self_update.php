<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Humbug\SelfUpdate\Updater;

class self_update extends Command
{

    protected $_is_wordpress;
    protected $_is_drupal;

    private $_io;

    protected function configure()
    {
        $this
            ->setName( 'self-update' )
            ->setDescription( 'Self update' )
        ;
    }

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

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = $this->get_or_create_io( $input, $output );

        $updater = new Updater(null, false);
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setPackageName('vendi-advertising/vendi-admin-cli');
        $updater->getStrategy()->setPharName('vendi-admin-cli.phar');
        $updater->getStrategy()->setCurrentLocalVersion( VENDI_CLI_APP_VERSION );

        try
        {
            $result = $updater->update();
            echo $result ? "Updated!\n" : "No update needed!\n";
            // $result = $updater->hasUpdate();
            // if( $result )
            // {
            //     printf( 'The current stable build available remotely is: %s', $updater->getNewVersion() );
            // }
            // elseif( false === $updater->getNewVersion() )
            // {
            //     echo "There are no stable builds available.\n";
            // }
            // else
            // {
            //     echo "You have the current stable build installed.\n";
            // }
        }
        catch( \Exception $e )
        {
            dump( $e );
            echo "Well, something happened! Either an oopsie or something involving hackers.\n";
            exit(1);
        }

        $io->note( 'Here' );
    }

}
