FROM php:8.2-apache

# Enable Apache rewrite (needed for your .htaccess routing rules)
RUN a2enmod rewrite

# Install PDO MySQL extension your repositories rely on
RUN docker-php-ext-install pdo_mysql

# Copy app source
COPY . /var/www/html

# Point Apache's docroot at /public, same fix your .htaccess already assumes
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf

# Install Composer and dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Apache listens on 80 by default; Render injects $PORT at *runtime*, not build time,
# so the vhost/ports config must be generated when the container starts, via this entrypoint:
RUN echo '#!/bin/bash\n\
set -e\n\
: "${PORT:=80}"\n\
echo "Listen ${PORT}" > /etc/apache2/ports.conf\n\
cat > /etc/apache2/sites-available/000-default.conf <<EOF\n\
<VirtualHost *:${PORT}>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n\
EOF\n\
exec apache2-foreground' > /usr/local/bin/start-apache.sh \
    && chmod +x /usr/local/bin/start-apache.sh

EXPOSE 80

CMD ["/usr/local/bin/start-apache.sh"]