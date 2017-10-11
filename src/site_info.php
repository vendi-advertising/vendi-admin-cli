<?php

namespace Vendi\CLI;

use Symfony\Component\Console\Style\SymfonyStyle;

class site_info
{
    const CMS_TYPE_WORDPRESS = 'WordPress';

    const CMS_TYPE_DRUPAL = 'Drupal';

    private $_client_name;

    private $_site_purpose;

    private $_site_year;

    private $_cms_type;

    private $_sub_domain;

    private $_stage_type;

    private $_database_name;

    private $_domain_base;

    public function show_summary( SymfonyStyle $io )
    {
        $io->table(
                    [
                        'Client Name',
                        'Site Purpose',
                        'Site Year',
                        'CMS Type',
                        'Stage Type',
                        'Subdomain',
                        'Domain Base',
                        'Database Name',
                        'Folder Name',
                    ],
                    [
                        [
                            $this->get_client_name(),
                            $this->get_site_purpose(),
                            $this->get_site_year(),
                            $this->get_cms_type(),
                            $this->get_stage_type(),
                            $this->get_sub_domain(),
                            $this->get_domain_base(),
                            $this->get_database_name(),
                            $this->get_top_level_folder_name(),
                        ]
                    ]
            );

        $io->text( 'The folder structure for this site will look like:' );

        //TODO: Site root needs to be handled
        $base = '/var/www/' . $this->get_top_level_folder_name();
        $cms_folder = $this->get_cms_type() === self::CMS_TYPE_WORDPRESS ? 'wp-site' : 'drupal-site';
        $directories = [
                            $base,
                            $base . '/' . $this->get_stage_type(),
                            $base . '/' . $this->get_stage_type() . '/' . 'logs',
                            $base . '/' . $this->get_stage_type() . '/' . 'nginx',
                            $base . '/' . $this->get_stage_type() . '/' . $cms_folder,
        ];

        $io->listing( $directories );
    }

    public function get_domain_base() : ?string
    {
        return $this->_domain_base;
    }

    public function get_client_name() : ?string
    {
        return $this->_client_name;
    }

    public function get_database_name() : ?string
    {
        return $this->_database_name;
    }

    public function get_stage_type() : ?string
    {
        return $this->_stage_type;
    }

    public function get_sub_domain() : ?string
    {
        return $this->_sub_domain;
    }

    public function get_site_purpose() : ?string
    {
        return $this->_site_purpose;
    }

    public function get_site_year() : ?int
    {
        return $this->_site_year;
    }

    public function get_cms_type() : ?string
    {
        return $this->_cms_type;
    }

    public function set_domain_base( string $domain_base )
    {
        $this->_domain_base = $domain_base;
    }

    public function set_database_name( string $database_name )
    {
        $this->_database_name = $database_name;
    }

    public function set_stage_type( string $stage_type )
    {
        $this->_stage_type = $stage_type;
    }

    public function set_client_name( string $client_name )
    {
        $this->_client_name = $client_name;
    }

    public function set_sub_domain( string $sub_domain )
    {
        $this->_sub_domain = $sub_domain;
    }

    public function with_cms_type_wordpress() : self
    {
        return $this->with_cms_type( self::CMS_TYPE_WORDPRESS );
    }

    public function with_cms_type_drupal() : self
    {
        return $this->with_cms_type( self::CMS_TYPE_DRUPAL );
    }

    public function set_cms_type( string $cms_type )
    {
        if( ! in_array( $cms_type, [ self::CMS_TYPE_WORDPRESS, self::CMS_TYPE_DRUPAL ] ) )
        {
            throw new \Exception( 'Invalid CMS type: ' . $cms_type );
        }

        $this->_cms_type = $cms_type;
    }

    public function set_site_year( int $site_year )
    {
        $this->_site_year = $site_year;
    }

    public function set_site_purpose( string $site_purpose )
    {
        $this->_site_purpose = $site_purpose;
    }

    public function with_database_name( string $database_name ) : self
    {
        $clone = clone $this;
        $clone->set_database_name( $database_name );
        return $clone;
    }

    public function with_cms_type( string $cms_type ) : self
    {
        $clone = clone $this;
        $clone->set_cms_type( $cms_type );
        return $clone;
    }

    public function with_stage_type( string $stage_type ) : self
    {
        $clone = clone $this;
        $clone->set_stage_type( $stage_type );
        return $clone;
    }

    public function with_site_year( int $site_year ) : self
    {
        $clone = clone $this;
        $clone->set_site_year( $site_year );
        return $clone;
    }

    public function with_client_name( string $client_name ) : self
    {
        $clone = clone $this;
        $clone->set_client_name( $client_name );
        return $clone;
    }

    public function with_site_purpose( string $site_purpose ) : self
    {
        $clone = clone $this;
        $clone->set_site_purpose( $site_purpose );
        return $clone;
    }

    public function with_sub_domain( string $sub_domain ) : self
    {
        $clone = clone $this;
        $clone->set_sub_domain( $sub_domain );
        return $clone;
    }

    public function get_database_stuff() : string
    {
        $parts = explode( ' ', strtolower( $this->get_client_name() ) );

        switch( $this->get_site_purpose() )
        {
            case 'Primary Site':
                //NOOP
                break;

            case 'Landing Page':
                $parts[] = 'lp';
                break;

            case 'Storefront':
                $parts[] = 'shop';
                break;

            default:
                $parts = array_merge( $parts, explode( ' ', strtolower( $this->get_site_purpose() ) ) );
                break;
        }

        if( $this->get_site_year() )
        {
            $parts[] = $this->get_site_year();
        }

        return self::letters_numbers_underscore_only( implode( '_', $parts ) );
    }

    public function generate_subdomain() : string
    {
        $parts = explode( ' ', strtolower( $this->get_client_name() ) );

        $host = implode( '-', $parts );

        $host = preg_replace( '/[^a-zA-Z0-9\-_]+/', '', $host );

        return $host;
    }

    public function get_top_level_folder_name() : string
    {
        $parts = explode( ' ', strtolower( $this->get_client_name() ) );

        $parts = array_merge( $parts, explode( ' ', strtolower( $this->get_site_purpose() ) ) );

        if( $this->get_site_year() )
        {
            $parts[] = $this->get_site_year();
        }

        return self::letters_numbers_dashes_only( implode( '-', $parts ) );
    }

    public static function letters_numbers_underscore_only( string $text ) : string
    {
        return preg_replace( '/[^0-9a-zA-Z_]/', '', $text );
    }

    public static function letters_numbers_dashes_only( string $text ) : string
    {
        return preg_replace( '/[^0-9a-zA-Z\-]/', '', $text );
    }
}
