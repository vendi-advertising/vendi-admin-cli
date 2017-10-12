## [2.1.0](https://github.com/vendi-advertising/vendi-admin-cli/releases/tag/2.1.0) - 2017-10-12
 * [Fixed WP-CLI glitch related to PHP bug](https://github.com/vendi-advertising/vendi-admin-cli/issues/8)
   * We now require and test for a WP-CLI version greater thatn 1.3.0
 * [Fixed sub-command bug related to Symfony Console upgrade from 3.2 to 3.3](https://github.com/vendi-advertising/vendi-admin-cli/issues/7)

## [2.0.5](https://github.com/vendi-advertising/vendi-admin-cli/releases/tag/2.0.5) - 2017-10-12
 * Build script now clones the repo and runs composer with `--no-dev` to greatly shrink final binary phar file

## [2.0.4](https://github.com/vendi-advertising/vendi-admin-cli/releases/tag/2.0.4) - 2017-10-11
 * Added support for domain base in addition to just subdomain
 * Better parsing of characters for domains, file system and database
