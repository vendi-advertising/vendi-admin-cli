<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vendi\CLI\nginx_template;
use Vendi\CLI\site_info;

class configure_nginx_command extends _base_with_fs
{
    private $subdomain;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName( 'configure-nginx' )
            ->setDescription( 'Configure nginx' )
        ;

        $this
            ->addArgument( 'subdomain', InputArgument::REQUIRED, 'The sanitized subdomain.' )
        ;
    }

    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        parent::initialize( $input, $output );
        $this->subdomain = $input->getArgument( 'subdomain' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = $this->get_or_create_io( $input, $output);

        $cms_folder = $this->_is_wordpress ? 'wp-site' : 'drupal-site';
        $cms_type = $this->_is_wordpress ? site_info::CMS_TYPE_WORDPRESS : site_info::CMS_TYPE_DRUPAL;

        $config = nginx_template::get_template_basic(
                                                        $this->subdomain,
                                                        $this->_folder_name,
                                                        $this->_stage_type,
                                                        $cms_type
            );

        $conf_file_original = sprintf( '/etc/nginx/sites-available/%1$s.helix.vendiadvertising.com', $this->_folder_name );
        if( false === file_put_contents( $conf_file_original, $config ) )
        {
            $io->error( 'Could not create nginx conf file' );
            exit;
        }

        $conf_file_link = sprintf( '/etc/nginx/sites-enabled/%1$s.helix.vendiadvertising.com', $this->_folder_name );
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


}
