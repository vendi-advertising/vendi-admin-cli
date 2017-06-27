<?php

namespace Vendi\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CreateSiteCommand extends Command
{

    private $_site_info;

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

    public function load_site_name( SymfonyStyle $io )
    {
        $site_type = $io->choice(
                                        'What CMS will be used for this site?',
                                        [ site_info::CMS_TYPE_WORDPRESS, site_info::CMS_TYPE_DRUPAL ]
                                    );
        $this->_site_info->set_cms_type( $site_type );

        $this->_site_info->set_client_name( $io->ask( 'What is the client\'s name?' ) );

        $purpose = $io->choice(
                                'What is the purpose of this site?',
                                [
                                    'Primary Site',
                                    'Blog',
                                    'Landing Page',
                                    'Storefront',
                                    'Other,'
                                ]
                            );

        if( 'Other' === $purpose )
        {
            $purpose = $io->ask( 'Please enter one or two words to describe this site.' );
        }

        $this->_site_info->set_site_purpose( $purpose );

        if( $io->confirm( 'Do you need a specific year designation? (Please avoid unless needed.)', false ) )
        {
            $year = $io->ask(
                                'Enter a four-digit year:',
                                (int)date( 'Y' ),
                                function( $number )
                                {
                                    $number = (int)$number;
                                    if( 4 !== strlen( $number ) )
                                    {
                                        throw new \RuntimeException( 'You must enter a four digit year.' );
                                    }

                                    return $number;
                                }
                            );

            $this->_site_info->set_site_year( (int)$year );
        }

        $this->_site_info->set_stage_type(
                                            $io->ask( 'What stage level should be used?', 'stage' )
            );
    }

    public function load_domain_stuff( SymfonyStyle $io )
    {
        $folder = $this->_site_info->get_top_level_folder_name();

        $domain = str_replace( '-primary-site', '', $folder );

        $parts = explode( '-', $domain );
        if( count( $parts ) <= 3 )
        {
            $guess_okay = $io->confirm( sprintf( 'Does the domain %1$s.helix.vendiadvertising.com work for you?', $domain ), true );
        }
        else
        {
            $guess_okay = ! $io->confirm( sprintf( 'The domain %1$s.helix.vendiadvertising.com seems kind of long, would you like to change it?', $domain ), true );
        }

        if( ! $guess_okay )
        {
            $domain = $io->ask( 'What subdomain would you like to use?' );
        }

        if( ! $domain )
        {
            throw new \Exception( 'No domain set' );
        }

        $this->_site_info->set_sub_domain( $domain );

    }

    public function load_database_stuff( SymfonyStyle $io )
    {
        //Username max = 32
        //Table max    = 64

        $db_stuff = $this->_site_info->get_database_stuff();

        $db_okay = false;

        if( strlen( $db_stuff ) <= 32 )
        {
            $db_okay = $io->confirm( sprintf( 'Does the database name %1$s work for you?', $db_stuff ) );
        }

        while( ! $db_okay )
        {
            $db_stuff = $io->ask( 'What database name would you like to use?' );

            if( $db_stuff !== strtolower( $db_stuff ) )
            {
                $io->note( 'Found uppercase letters, making lowercase.' );
                $db_stuff = strtolower( $db_stuff );
            }

            if( strlen( $db_stuff ) > 32 )
            {
                $io->caution( 'The database name must be 32 characters or less.' );
                continue;
            }

            if( site_info::letters_numbers_underscore_only( $db_stuff ) !== $db_stuff )
            {
                $io->caution( 'Please only use letters, numbers and underscores.' );
                continue;
            }

            $left = $db_stuff[ 0 ];
            if( ctype_digit( $left ) )
            {
                $io->caution( 'The first character must not be a digit.' );
                continue;
            }

            $db_okay = true;
        }

        $this->_site_info->set_database_name( $db_stuff );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new SymfonyStyle($input, $output);
        $io->write( sprintf( "\033\143" ) );
        $io->title('Vendi site creation wizard');
        $io->text(
                    [
                        'Welcome to the Vendi Advertising site setup wizard for the Helix server.',
                        'The inital round of questions will attempt to determine the proper settings for your client\'s website. No changes will be made during this round.',
                        'If a question ends with text in square brackets then that is the default and you may just press enter.',
                        'Some basic rules that will (hopefully) be enforced:',
                    ]
            );

        $io->section( 'Database' );
        $io->listing(
                        [
                            'Can only contain ASCII letters, numbers and underscores',
                            '32 characters or less',
                            'Cannot start with a number',
                        ]
            );

        $io->section( 'Subdomain' );
        $io->listing(
                        [
                            'Can only contain ASCII letters, numbers and dashes',
                            'Ideally 2 or 3 "words"',
                        ]
            );

        if( 0 !== posix_getuid() )
        {
            $io->error( 'This command needs to run as root. Please re-run it with sudo privileges.' );
            exit;
        }

        if( ! $io->confirm( 'Are you ready to start?', true ) )
        {
            $io->success( 'Okay, well I did\'t do anything but I hope you come back!' );
            exit;
        }

        $wizard_ok = false;

        $directories = null;

        while( ! $wizard_ok )
        {
            $this->_site_info = new site_info();
            $this->load_site_name( $io );
            $this->load_domain_stuff( $io );
            $this->load_database_stuff( $io );

            $io->table(
                        [
                            'Client Name',
                            'Site Purpose',
                            'Site Year',
                            'CMS Type',
                            'Stage Type',
                            'Subdomain',
                            'Database Name',
                            'Folder Name',
                        ],
                        [
                            [
                                $this->_site_info->get_client_name(),
                                $this->_site_info->get_site_purpose(),
                                $this->_site_info->get_site_year(),
                                $this->_site_info->get_cms_type(),
                                $this->_site_info->get_stage_type(),
                                $this->_site_info->get_sub_domain(),
                                $this->_site_info->get_database_name(),
                                $this->_site_info->get_top_level_folder_name(),
                            ]
                        ]
                );

            $io->text( 'The folder structure for this site will look like:' );

            $base = '/var/www/' . $this->_site_info->get_top_level_folder_name();
            $cms_folder = $this->_site_info->get_cms_type() === site_info::CMS_TYPE_WORDPRESS ? 'wp-site' : 'drupal-site';
            $directories = [
                                $base,
                                $base . '/' . $this->_site_info->get_stage_type(),
                                $base . '/' . $this->_site_info->get_stage_type() . '/' . 'logs',
                                $base . '/' . $this->_site_info->get_stage_type() . '/' . 'nginx',
                                $base . '/' . $this->_site_info->get_stage_type() . '/' . $cms_folder,
            ];
            $io->listing( $directories );

            $wizard_ok = $io->confirm( 'Does everything above look correct?', true );

            if( ! $wizard_ok )
            {
                $io->warning( 'Ok. Unfortunately I need to reset everything you did. Sorry.' );
            }
        }

        if( $io->confirm( 'Would you like me to create the above folder structire (if it doesn\'t already exist?', true ) )
        {
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
        else
        {
            $io->warning( 'Skipping folder creation, this part is up to you!' );
        }

        if( $io->confirm( 'Would you like me to create the database?', true ) )
        {
            $io->note( 'If you are running this on the Helix server just type anything when prompted for a password.' );

            $command = sprintf(
                                'VENDI_RESULT=$(echo "CREATE DATABASE IF NOT EXISTS %1$s; GRANT ALL PRIVILEGES ON %1$s.* TO %1$s@localhost IDENTIFIED BY \'%1$s\'; FLUSH PRIVILEGES; SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \'%1$s\';" | mysql -uroot -p); echo $VENDI_RESULT;',
                                $this->_site_info->get_database_name()
            );

            $result = @exec( $command );

            if( 'SCHEMA_NAME ' . $this->_site_info->get_database_name() !== $result )
            {
                $io->error( 'Unknown problem while trying to create database... exiting.' );
                exit;
            }

            $io->success( sprintf( 'Created database %1$s', $this->_site_info->get_database_name() ) );

        }
        else
        {
            $io->warning( 'Skipping database creation, this part is up to you!' );
        }

        if( $io->confirm( 'Would you like me to download the latest CMS?' ) )
        {
            $cms_folder = $this->_site_info->get_cms_type() === site_info::CMS_TYPE_WORDPRESS ? 'wp-site' : 'drupal-site';

            switch( $this->_site_info->get_cms_type() )
            {
                case site_info::CMS_TYPE_DRUPAL:
                    $command = sprintf( 'drush dl drupal --destination=/var/www/%1$s/%2$s/%3$s --drupal-project-rename=tmp --yes',
                        $this->_site_info->get_top_level_folder_name(),
                        $this->_site_info->get_stage_type(),
                        $cms_folder
                    );

                    $result = exec( $command );
                    dump( $result );

                    // Identify directories
                    $source = sprintf(
                                        '/var/www/%1$s/%2$s/%3$s/tmp/',
                                        $this->_site_info->get_top_level_folder_name(),
                                        $this->_site_info->get_stage_type(),
                                        $cms_folder
                                );

                    $destination = sprintf(
                                        '/var/www/%1$s/%2$s/%3$s/',
                                        $this->_site_info->get_top_level_folder_name(),
                                        $this->_site_info->get_stage_type(),
                                        $cms_folder
                                );

                    // Get array of all source files
                    $files = scandir( $source);

                    // Cycle through all source files
                    foreach( $files as $file )
                    {
                        if( in_array( $file, array( '.', '..' ) ) )
                        {
                            continue;
                        }

                        // If we copied this successfully, mark it for deletion
                        if( ! rename( $source . $file, $destination . $file ) )
                        {
                            $io->error( sprintf( 'Could not move file %1$s from temporary location.', $file ) );
                        }
                    }

                    if( ! rmdir( $source ) )
                    {
                        $io->error( 'Could not remove temporary folder... something wrong probably happened.' );
                        exit;
                    }

                    break;

                case site_info::CMS_TYPE_WORDPRESS:
                    $command = sprintf( 'wp core download --path=/var/www/%1$s/%2$s/%3$s --allow-root',
                        $this->_site_info->get_top_level_folder_name(),
                        $this->_site_info->get_stage_type(),
                        $cms_folder
                    );

                    $result = exec( $command );
                    dump( $result );
                    break;
            }

            $io->success( 'I think that worked, not 100% sure. Maybe check that.' );
        }
        else
        {
            $io->warning( 'Skipping CMS download, this part is up to you!' );
        }

        if( $io->confirm( 'Would you like me to create the nginx entries?', true ) )
        {
            $config = nginx_template::get_template_basic(
                                                            $this->_site_info->get_sub_domain(),
                                                            $this->_site_info->get_top_level_folder_name(),
                                                            $this->_site_info->get_stage_type(),
                                                            $this->_site_info->get_cms_type()
                );

            $conf_file_original = sprintf( '/etc/nginx/sites-available/%1$s.helix.vendiadvertising.com', $this->_site_info->get_top_level_folder_name() );
            if( false === file_put_contents( $conf_file_original, $config ) )
            {
                $io->error( 'Could not create nginx conf file' );
                exit;
            }

            $conf_file_link = sprintf( '/etc/nginx/sites-enabled/%1$s.helix.vendiadvertising.com', $this->_site_info->get_top_level_folder_name() );
            if( ! symlink( $conf_file_original, $conf_file_link ) )
            {
                $io->error( 'Could not create nginx symlink' );
                exit;
            }

            $io->success( sprintf( 'Created nginx file %1$s', $conf_file_original ) );
            $io->success( sprintf( 'Created nginx file %1$s', $conf_file_link ) );

            $result = exec( 'nginx -t' );

            if( strpos( $result, '[emerg]' ) )
            {
                $io->error( 'Nginx conf error, config failed test.' );
                $io->error( $result );
                exit;
            }

            $io->success( 'Nginx files appear valid... reload service' );

            $result = exec( 'service nginx reload' );

            $io->success( 'Succesfully reload nginx server' );
        }
        else
        {
            $io->warning( 'Skipping nginx entry creation, this part is up to you!' );
        }

        $io->text( 'Well, that\'s about it. This would be a great place for a summary!' );
    }
}