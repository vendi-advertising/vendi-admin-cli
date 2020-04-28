<?php

namespace Vendi\CLI;

use RuntimeException;
use Webmozart\PathUtil\Path;

final class config_info
{
    private string $_config_file_name = 'vendi-admin-cli.yml';

    public const CONFIG_KEY_GLOBAL = 'global';

    public const CONFIG_KEY_USER = 'user';

    public function get_config_paths(): array
    {
        $dir = null;
        try {
            $dir = Path::getHomeDirectory();
        } catch (\Exception $e) {
            throw new RuntimeException('Could not determine your home folder for some weird reason... exiting.', 0, $e);
        }

        return [
            self::CONFIG_KEY_GLOBAL => Path::join('/etc/', $this->_config_file_name),
            self::CONFIG_KEY_USER => Path::join($dir, '.' . $this->_config_file_name),
        ];
    }
}
