<?php

$p = new \Phar( 'vendi-admin-cli.phar', 0 );
foreach(new \RecursiveIteratorIterator( $p ) as $file )
{
    echo $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename() . PHP_EOL;
}
