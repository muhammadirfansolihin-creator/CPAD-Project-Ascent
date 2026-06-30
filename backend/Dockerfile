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

# Apache listens on 80 by default; Railway maps $PORT automatically via its proxy,
# but Apache itself doesn't read $PORT — so override the listen port at runtime:
RUN echo '<VirtualHost *:${PORT}>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf

EXPOSE 80

CMD ["apache2-foreground"]