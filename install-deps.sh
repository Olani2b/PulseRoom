#!/bin/bash

# Install Composer inside the container and run composer install
docker compose exec -T php_8.2_apache_container bash -c "cd /var/www/html && curl -sS https://getcomposer.org/installer | php && php composer.phar install && rm composer.phar"
