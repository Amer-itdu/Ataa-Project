#!/bin/bash

# تثبيت composer بمكان دائم لو مش موجود
if [ ! -f /home/site/ext/composer.phar ]; then
    mkdir -p /home/site/ext
    curl -sS https://getcomposer.org/installer -o /home/site/ext/composer-setup.php
    php /home/site/ext/composer-setup.php --install-dir=/home/site/ext --filename=composer.phar
fi
cp /home/site/ext/composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

mkdir -p /home/site/wwwroot/storage/framework/cache/data
mkdir -p /home/site/wwwroot/storage/framework/sessions
mkdir -p /home/site/wwwroot/storage/framework/views
mkdir -p /home/site/wwwroot/storage/framework/testing
mkdir -p /home/site/wwwroot/storage/app/public
mkdir -p /home/site/wwwroot/storage/logs
chmod -R 775 /home/site/wwwroot/storage
chmod -R 775 /home/site/wwwroot/bootstrap/cache
cp /home/site/wwwroot/nginx.conf /etc/nginx/sites-enabled/default
service nginx reload

cd /home/site/wwwroot
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force