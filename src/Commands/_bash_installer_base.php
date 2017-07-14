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

    const DEFAULT_TIMEOUT = 100;

    private $_io;

    private $_next_command_timeout = 100;

    private $_in_multi_command = false;

    private $_warn_about_long_running_command = false;

    public function set_io( SymfonyStyle $io )
    {
        $this->_io = $io;
    }

    protected function test_requirements( bool $quiet = false ) : bool
    {
        throw new \Exception( 'Child classes must implement the method test_requirements.' );
    }

    protected function test_install( bool $quiet = false ) : bool
    {
        throw new \Exception( 'Child classes must implement the method test_install.' );
    }

    final protected function set_next_command_timeout( int $seconds )
    {
        if( $seconds > self::DEFAULT_TIMEOUT )
        {
            $this->_warn_about_long_running_command = true;
        }

        $this->_next_command_timeout = $seconds;
    }

    final protected function reset_next_command_timeout( )
    {
        $this->set_next_command_timeout( self::DEFAULT_TIMEOUT );
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

    protected function _run_mulitple_commands_with_working_directory( array $commands, string $working_directory = null, bool $quiet = false ) : bool
    {
        $this->_in_multi_command = true;

        foreach( $commands as $command )
        {
            if( ! $this->_run_command_with_working_directory( $command, "An unknown error occurred while running command ${command}" , $working_directory, $quiet ) )
            {
                $this->_in_multi_command = false;
                $this->reset_next_command_timeout();
                return false;
            }
        }

        $this->_in_multi_command = false;
        $this->reset_next_command_timeout();
        return true;
    }

    /**
     * [_run_command_with_working_directory description]
     *
     * This is the workhorse function. All run commands lead here so be careful
     * when editing.
     *
     * @param  string       $command               [description]
     * @param  string       $failure_error_message [description]
     * @param  string|null  $working_directory     [description]
     * @param  bool|boolean $quiet                 [description]
     * @return [type]                              [description]
     */
    protected function _run_command_with_working_directory( string $command, string $failure_error_message, string $working_directory = null, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        if( $this->_warn_about_long_running_command )
        {
            if( ! $quiet )
            {
                $io->note( sprintf( 'The next command has been allowed %1$s seconds to run, please be patient', number_format( $this->_next_command_timeout ) ) );
            }
            $this->_warn_about_long_running_command = false;
        }

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
                if( ! $this->_in_multi_command )
                {
                    $this->reset_next_command_timeout();
                }
                return false;
            }
            $io->error( "An unknown error occurred while creating a process for the following command:" );
            $io->error( $command );
            exit;
        }

        $exit_code = null;
        for( $i = 0; $i < $this->_next_command_timeout; $i++ )
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

        //TODO: We're not handling dangling processes above, I think we need to
        //call proc_get_status( $process ) one last time.

        $stdout = stream_get_contents( $pipes[ 1 ] );
        fclose( $pipes[ 1 ] );

        $stderr = stream_get_contents( $pipes[ 2 ] );
        fclose( $pipes[ 2 ] );

        proc_close( $process );

        if( 0 !== $exit_code )
        {
            if( $quiet )
            {
                if( ! $this->_in_multi_command )
                {
                    $this->reset_next_command_timeout();
                }
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

        if( ! $this->_in_multi_command )
        {
            $this->reset_next_command_timeout();
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

    protected function echo_to_file( string $text, string $file, bool $erase_current_file = false, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $redirection = $erase_current_file ? '>' : '>>';

        $command = "echo -e \"${text}\" ${redirection} ${file}";

        $this->_run_command( $command, "An unknown error occurred while attempting to echo ${text} to file ${file}", $quiet );

        $io->success( "Successfully echoed ${text} to ${file}" );

        return true;
    }

    protected function create_symlink( string $source_file, string $dest_file, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "ln -sf ${source_file} ${dest_file}";

        $this->_run_command( $command, "An unknown error occurred while attempting to create symbolic link from ${source_file} to ${dest_file}", $quiet );

        $io->success( "Successfully created symbolic link from ${source_file} to ${dest_file}" );

        return true;
    }

    protected function remove_directory( string $directory, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "rm -rf ${directory}";

        $this->_run_command( $command, "An unknown error occurred while attempting to remove directory ${directory}", $quiet );

        $io->success( "Successfully removed directory ${directory}" );

        return true;
    }

    protected function remove_file( string $file, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "rm ${file}";

        $this->_run_command( $command, "An unknown error occurred while attempting to remove file ${file}", $quiet );

        $io->success( "Successfully removed file ${file}" );

        return true;
    }

    protected function get_remote_url( string $remote_url, string $local_folder, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "wget ${remote_url}";

        $this->_run_command_with_working_directory( $command, "An unknown error occurred while attempting to download remote URL ${remote_url}", $local_folder, $quiet );

        $io->success( "Successfully downloaded remote URL ${remote_url}" );

        return true;
    }

    protected function untar_file( string $file, string $local_folder, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "tar xzvf ${file}";

        $this->_run_command_with_working_directory( $command, "An unknown error occurred while attempting to untar file ${file}", $local_folder, $quiet );

        $io->success( "Successfully untar'd file ${file}" );

        return true;
    }

    protected function pecl_install( string $name, bool $quiet = false ) : bool
    {
        $io = $this->get_or_create_io();

        $command = "pecl channel-update pecl.php.net && pecl install -f ${name}";

        $this->_run_command_with_working_directory( $command, "An unknown error occurred while attempting to install PECL package ${name}", $quiet );

        $io->success( "Successfully installed PECL package ${name}" );

        return true;
    }
}
