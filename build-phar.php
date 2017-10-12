<?php

define( 'APP_DIR',   './releases/' );
define( 'APP_ALIAS', 'vendi-admin-cli.phar' );
define( 'APP_FILE',  APP_DIR . APP_ALIAS );

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

use Symfony\Component\Finder\Finder;

if( ! is_dir( APP_DIR ) )
{
    mkdir( APP_DIR );
}

if( is_file( APP_FILE ) )
{
    unlink( APP_FILE );
}

$phar = new \Phar( APP_FILE, 0, APP_ALIAS );
$phar->compressFiles( \Phar::GZ );
$phar->setSignatureAlgorithm( \Phar::SHA256 );

// PHP files
$finder = new Finder();
$finder
    ->files()
    ->ignoreVCS( true )
    ->name( '*.php' )
    ->in( './src' )
    ->in( './vendor' )
    ->in( './includes' )
    ->exclude('test')
    ->exclude('tests')
    ->exclude('Test')
    ->exclude('Tests')
    ->exclude('symfony/var-dumper')
    ->exclude('symfony/finder')
//    ->exclude('php-cli-tools/examples')
    ;

$files = array();
$files['app.php'] = './app.php';

foreach ( $finder as $file )
{
    $files[ substr( $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename(), 2 ) ] = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();
}

$phar->buildFromIterator(new ArrayIterator($files));
$phar->setStub( <<<EOB
#!/usr/bin/env php
<?php
Phar::mapPhar();
define( 'VENDI_CLI_ROOT_PHAR', 'phar://vendi-admin-cli.phar' );
include VENDI_CLI_ROOT_PHAR . '/app.php';
__HALT_COMPILER();
?>
EOB
);

$phar = null;
