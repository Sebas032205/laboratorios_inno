FROM php:8.2-apache

# Instalar las extensiones de PHP indispensables para conectar con MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar el módulo de reescritura de Apache (muy útil para rutas amigables)
RUN a2enmod rewrite

# Exponer el puerto 80 interno del contenedor
EXPOSE 80
