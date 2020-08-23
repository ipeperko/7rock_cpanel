#!/bin/bash

php uploaddir_local.php "$*"
sudo chown -R www-data:www-data /var/www/html/uploads
