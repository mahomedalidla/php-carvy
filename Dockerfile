# Fase 1: Instalar dependencias con Composer
# Usamos la imagen oficial de Composer para esto
FROM composer:2 as vendor

WORKDIR /app
# Copiamos solo los archivos de dependencias para aprovechar el cache de Docker
COPY composer.json composer.lock ./
# Instalamos las dependencias de producción
RUN composer install --no-dev --no-interaction --optimize-autoloader


# Fase 2: Construir la imagen final de la aplicación
# Usamos la imagen oficial de PHP con el servidor web Apache
FROM php:8.2-apache

# Establecemos el directorio de trabajo de Apache
WORKDIR /var/www/html

# Copiamos las dependencias que instalamos en la fase anterior
COPY --from=vendor /app/vendor/ /var/www/html/vendor/

# Copiamos el resto de los archivos de la aplicación
COPY . .

# Creamos el directorio de salida y nos aseguramos de que el servidor Apache (www-data)
# tenga permisos para escribir en él.
RUN mkdir -p output && chown -R www-data:www-data output

# Exponemos el puerto 80, que es el que usa Apache por defecto
EXPOSE 80

# El servidor Apache se inicia automáticamente con esta imagen base.
