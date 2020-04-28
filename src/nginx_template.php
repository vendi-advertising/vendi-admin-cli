<?php

namespace Vendi\CLI;

final class nginx_template
{
    public static function get_template_basic($subdomain, $folder, $stage_type, $cms_type, $domain_base): string
    {
        $extra = '';
        $cms_folder = '';

        switch ($cms_type) {
            case site_info::CMS_TYPE_WORDPRESS:
                $extra = 'include global/all-in-one/wordpress-secure-block.conf;';
                $cms_folder = 'wp-site';
                break;

            case site_info::CMS_TYPE_DRUPAL:
                $extra = 'include global/drupal/drupal.conf;';
                $cms_folder = 'drupal-site';
                break;
        }

        return sprintf(
            '
server {
        listen 80;
        server_name %1$s.%6$s;
        return 301 https://$host$request_uri;
}
server {
        listen 443 ssl http2;

        server_name %1$s.%6$s;
        root /var/www/%2$s/%3$s/%4$s;

        access_log     /var/www/%2$s/%3$s/logs/access.log vhosts;
        error_log      /var/www/%2$s/%3$s/logs/error.log error;

        %5$s

        include /var/www/%2$s/%3$s/nginx/*.conf;
}
            ',
            $subdomain,
            $folder,
            $stage_type,
            $cms_folder,
            $extra,
            $domain_base
        );
    }
}
