<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Vendi\Shared\fs_utils;

class _bash_installer_base extends Command
{

    private $_io;

    public function set_io( SymfonyStyle $io )
    {
        $this->_io = $io;
    }

    protected function test_install( bool $quiet = false ) : bool
    {
        throw new \Exception( 'Child classes must implement the method test_install.' );
    }

    protected function get_or_create_io( InputInterface $input = null, OutputInterface $output = null ) : SymfonyStyle
    {
        if( ! $this->_io )
        {
            if( ! $input || ! $output )
            {
                throw new \Exception( 'You must either initialize IO elsewhere or provide Input and Output to do so here.' );
            }

            $this->_io = new SymfonyStyle( $input, $output );
        }
        return $this->_io;
    }

    protected function _run_command_with_working_directory( string $command, string $failure_error_message, string $working_directory = null, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $descriptorspec = [
                               0 => [ 'pipe', 'r' ],  // stdin
                               1 => [ 'pipe', 'w' ],  // stdout
                               2 => [ 'pipe', 'w' ],  // stderr
                    ];

        $process = proc_open( $command, $descriptorspec, $pipes, $working_directory );
        if( ! is_resource( $process ) )
        {
            if( $quiet )
            {
                return false;
            }
            $io->error( "An unknown error occurred while creating a process for the following command:" );
            $io->error( $command );
            exit;
        }

        $exit_code = null;
        for( $i = 0; $i < 100; $i++ )
        {
            $status = proc_get_status( $process );
            if( ! $status[ 'running' ] )
            {
                $exit_code = $status[ 'exitcode' ];
                break;
            }

            // dump( $status );
            sleep( 1 );
        }

        $stdout = stream_get_contents( $pipes[ 1 ] );
        fclose( $pipes[ 1 ] );

        $stderr = stream_get_contents( $pipes[ 2 ] );
        fclose( $pipes[ 2 ] );

        proc_close( $process );

        if( 0 !== $exit_code )
        {
            if( $quiet )
            {
                return false;
            }

            $io->error( $failure_error_message );
            if( $stderr )
            {
                $io->error( $stderr );
            }

            if( $stdout )
            {
                $io->error( $stdout );
            }

            exit;
        }

        return true;
    }

    protected function _create_tmp_folder( ) : string
    {
        return fs_utils::create_random_temp_dir( 'ADMIN_CLI' );
    }

    protected function _run_command( string $command, string $failure_error_message, bool $quiet = false ) : bool
    {
        return $this->_run_command_with_working_directory( $command, $failure_error_message, null, $quiet );
    }

    protected function update_apt_get( bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = 'apt-get update';

        $result = $this->_run_command( $command, '', $quiet );

        $io->success( "Successfully updated package cache" );

        return true;
    }

    protected function install_package( string $name, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $multiple = count( explode( ' ', trim( $name ) ) ) > 1;

        $package_string = $multiple ? 'packages' : 'package';

        $command = "apt-get install ${name} --yes";

        $this->_run_command( $command, "An unknown error occurred while attempting to install ${package_string} ${name}", $quiet );

        $io->success( "Successfully installed ${package_string} ${name}" );

        return true;
    }

    protected function add_ppa( string $name, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "add-apt-repository ${name} -y";

        $this->_run_command( $command, "An unknown error occurred while adding PPA ${name}", $quiet );

        $io->success( "Successfully installed PPA ${name}" );

        return true;
    }

    protected function add_apt_key( string $key_server, string $key, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "apt-key adv --recv-keys --keyserver $key_server ${key}";

        $this->_run_command( $command, "An unknown error occurred while attempting to import key server ${key_server} with key ${key}", $quiet );

        $io->success( "Successfully importec key server ${key_server} with key ${key}" );

        return true;
    }

    protected function clone_git_repo( string $local_folder, string $repo, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "git clone ${repo} ${local_folder}";

        $this->_run_command( $command, "An unknown error occurred while attempting to checkout git repo ${repo}", $quiet );

        $io->success( "Successfully checked out git repo ${repo} to folder ${local_folder}" );

        return true;
    }

    protected function restart_service( string $name, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "service ${name} restart";

        $this->_run_command( $command, "An unknown error occurred while attempting to restart service ${name}", $quiet );

        $io->success( "Successfully restarted service ${name}" );

        return true;
    }

    protected function echo_to_file( string $text, string $file, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "echo -e \"${text}\" >> ${file}";

        $this->_run_command( $command, "An unknown error occurred while attempting to echo ${text} to file ${file}", $quiet );

        $io->success( "Successfully echoed ${text} to ${file}" );

        return true;
    }

}
