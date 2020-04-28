<?php

namespace Vendi\CLI\Commands;

class _bash_installer_with_php_base extends _bash_installer_base
{
    public const PHP_VERSION = '7.4';

    final protected function is_php_installed(): bool
    {
        $command = 'type php && php -v';

        if (!$this->_run_command($command, '', true)) {
            return false;
        }

        return true;
    }
}
