<?php

namespace Vendi\CLI\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class cms_download_command extends _base_with_fs
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('download-cms')
            ->setHidden(true)
            ->setDescription('Download the most recent CMS');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->get_or_create_io($input, $output);

        $cms_folder = $this->_is_wordpress ? 'wp-site' : 'drupal-site';

        if ($this->_is_drupal) {
            $command = sprintf('drush dl drupal --destination=/var/www/%1$s/%2$s/%3$s --drupal-project-rename=tmp --yes',
                $this->_folder_name,
                $this->_stage_type,
                $cms_folder
            );

            $result = exec($command);
            echo $result;

            // Identify directories
            $source = sprintf(
                '/var/www/%1$s/%2$s/%3$s/tmp/',
                $this->_folder_name,
                $this->_stage_type,
                $cms_folder
            );

            $destination = sprintf(
                '/var/www/%1$s/%2$s/%3$s/',
                $this->_folder_name,
                $this->_stage_type,
                $cms_folder
            );

            // Get array of all source files
            $files = scandir($source);

            // Cycle through all source files
            foreach ($files as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }

                // If we copied this successfully, mark it for deletion
                if (!rename($source . $file, $destination . $file)) {
                    $io->error(sprintf('Could not move file %1$s from temporary location.', $file));
                }
            }

            if (!rmdir($source)) {
                $io->error('Could not remove temporary folder... something wrong probably happened.');
                exit;
            }
        } elseif ($this->_is_wordpress) {
            $command = sprintf('wp core download --path=/var/www/%1$s/%2$s/%3$s --allow-root',
                $this->_folder_name,
                $this->_stage_type,
                $cms_folder
            );

            $result = exec($command);
            echo $result;
        }

        $io->success('I think that worked, not 100% sure. Maybe check that.');

    }

}
