FROM php:8.2-apache

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpng-dev libonig-dev libxml2-dev libzip-dev npm nodejs \
    && docker-php-ext-install pdo pdo_mysql zip

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Activer mod_rewrite
RUN a2enmod rewrite

# Changer le DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# Copier les fichiers du projet
COPY . /var/www/html
WORKDIR /var/www/html

# Installer les dépendances PHP et JS
RUN composer install \
    && npm install \
    && npm run build || node-sass public/scss/style.scss public/css/style.css || true

# Donner les bons droits
RUN chown -R www-data:www-data /var/www/html

# Exposer le port Apache
EXPOSE 80

CMD ["apache2-foreground"]
