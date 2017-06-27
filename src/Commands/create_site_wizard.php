<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;

class create_site_wizard extends Command
{

    private $_site_info;

    protected function configure()
    {
        $this
            ->setName('create-site-wizard')
            ->setDescription('Create a new site')
        ;
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


        $sub_commands = [
                'create-file-system' => [
                            'message'   => 'Would you like me to create the above folder structure (if it doesn\'t already exist)?',
                            'skip'      => 'Skipping folder creation, this part is up to you!',
                            'arguments' => [
                                    'command'               => 'create-file-system',
                                    'top-level-folder-name' => $this->_site_info->get_top_level_folder_name(),
                                    '--stage-type'          => $this->_site_info->get_stage_type(),
                                    '--cms-type-wordpress'  => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_WORDPRESS,
                                    '--cms-type-drupal'     => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_DRUPAL,
                            ],
                            'default'   => true,
                        ],
                'create-database' => [
                            'message'   => 'Would you like me to create the database?',
                            'skip'      => 'Skipping database creation, this part is up to you!',
                            'arguments' => [
                                    'command'               => 'create-database',
                                    'database-name'         => $this->_site_info->get_database_name(),
                                    '--cms-type-wordpress'  => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_WORDPRESS,
                                    '--cms-type-drupal'     => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_DRUPAL,
                            ],
                            'default'   => true,
                        ],
                'download-cms' => [
                            'message'   => 'Would you like me to download the latest CMS?',
                            'skip'      => 'Skipping CMS download, this part is up to you!',
                            'arguments' => [
                                    'command'               => 'download-cms',
                                    'top-level-folder-name' => $this->_site_info->get_top_level_folder_name(),
                                    '--stage-type'          => $this->_site_info->get_stage_type(),
                                    '--cms-type-wordpress'  => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_WORDPRESS,
                                    '--cms-type-drupal'     => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_DRUPAL,
                            ],
                            'default'   => true,
                        ],
                'configure-nginx' => [
                            'message'   => 'Would you like me to configure nginx for you?',
                            'skip'      => 'Skipping nginx configuration, this part is up to you!',
                            'arguments' => [
                                    'command'               => 'configure-nginx',
                                    'top-level-folder-name' => $this->_site_info->get_top_level_folder_name(),
                                    'subdomain'             => $this->_site_info->get_sub_domain(),
                                    '--stage-type'          => $this->_site_info->get_stage_type(),
                                    '--cms-type-wordpress'  => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_WORDPRESS,
                                    '--cms-type-drupal'     => $this->_site_info->get_cms_type() === site_info::CMS_TYPE_DRUPAL,
                            ],
                            'default'   => true,
                        ],
            ];

        foreach( $sub_commands as $name => $parts )
        {
            if( $io->confirm( $parts[ 'message' ], $parts[ 'default' ] ) )
            {

                $returnCode = $this
                                ->getApplication()
                                ->find( $name )
                                ->run( new ArrayInput( $parts[ 'arguments' ] ), $output)
                            ;
            }
            else
            {
                $io->warning( $parts[ 'skip' ] );
            }
        }

        $io->text( 'Well, that\'s about it. This would be a great place for a summary!' );
    }
}
