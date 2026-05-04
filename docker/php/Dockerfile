FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

# Cadenas y htmlspecialchars alineados con UTF-8 (coincide con utf8mb4 en MySQL y meta charset en HTML).
RUN printf '%s\n' 'default_charset=UTF-8' > /usr/local/etc/php/conf.d/99-charset.ini
