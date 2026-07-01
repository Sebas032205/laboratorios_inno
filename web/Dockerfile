FROM php:8.2-apache

# Instalar las extensiones de PHP indispensables para conectar con MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instalar y habilitar la extensión de Redis para PHP
RUN pecl install redis && docker-php-ext-enable redis

# Habilitar el módulo de reescritura de Apache (muy útil para rutas amigables)
RUN a2enmod rewrite

# Exponer el puerto 80 interno del contenedor
EXPOSE 80
