# vendi-admin-cli

## Installation
```
pushd ~
wget $(curl -s https://api.github.com/repos/vendi-advertising/vendi-admin-cli/releases/latest | grep browser_download_url | cut -d '"' -f 4)
sudo mv vendi-admin-cli.phar /usr/local/bin/vendi-admin-cli
sudo chmod +x /usr/local/bin/vendi-admin-cli
popd
```
