<?php

namespace Vendi\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CreateSiteCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create-site')
            ->setDescription('Create a new site')
        ;
    }

    public function untrailingslashit( $string )
    {
        return rtrim( $string, '/\\' );
    }

    public function trailingslashit( $string )
    {
        return $this->untrailingslashit( $string ) . '/';
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $helper = $this->getHelper( 'question' );


        $question = new ChoiceQuestion(
                                        'What type of site would you like to create?',
                                        array( 'WordPress', 'Drupal' )
                                    );
        $question->setErrorMessage( 'Type %s is invalid.' );
        $type = $helper->ask( $input, $output, $question );

        $question = new Question( 'Enter the domain that you want to use (enter a single word if you want to use *.eagle.vendiadvertising.com):' . "\n > " );
        $domain = $helper->ask( $input, $output, $question );

        if( false === strpos( $domain, '.' ) )
        {
            $domain .= '.eagle.vendiadvertising.com';
        }

        $question = new Question( 'What folder would you like to use?' . "\n > " );
        $folder = $helper->ask( $input, $output, $question );

        if( false === strpos( $folder, '/' ) )
        {
            $folder = '/var/www/' . $this->trailingslashit( $folder );
        }

        $question = new Question( 'Enter the site\'s instance (default "stage"):' . "\n > ", 'stage' );
        $instance = $helper->ask( $input, $output, $question );

        try
        {
            $folder_creation_status = $this->create_folders_wordpress( $folder, $instance );
        }
        catch( \Exception $ex )
        {
            $output->writeln( '<error>' . $ex->getMessage() . '<error>' );
            exit;
        }

        $config = $this->create_nginx_file_wordpress( $domain, $folder, $instance );


        $output->writeln( $config );
    }

    public function create_folders_wordpress( $folder, $instance )
    {
        $folder = $this->trailingslashit( $folder );
        $instance = $this->trailingslashit( $instance );

        $folders = array(
                            $folder,
                            $folder . $instance,
                            $folder . $instance . 'logs',
                            $folder . $instance . 'wp-site',
                    );

        foreach( $folders as $f )
        {
            if( ! is_dir( $f ) )
            {
                if( ! @mkdir( $f ) )
                {
                    throw new \Exception( 'Unable to create path: ' . $f );
                }
            }            
        }
    }

    public function create_nginx_file_wordpress( $domain, $folder, $instance )
    {
        $folder = $this->trailingslashit( $folder );
        $instance = $this->trailingslashit( $instance );

        $config = <<<EOT
server {
        listen 80;
        server_name {$domain};
        return 301 https://\$host\$request_uri;
}
server {
        listen 443 ssl http2;

        server_name {$domain};
        root {$folder}{$instance}wp-site;

        include global/ssl-wildcard.conf;

        access_log     {$folder}{$instance}logs/access.log vhosts;
        error_log      {$folder}{$instance}logs/error.log error;

        index index.php;

        include global/restrictions.conf;
        include global/wordpress.conf;
}

EOT;
        return $config;
    }
}
