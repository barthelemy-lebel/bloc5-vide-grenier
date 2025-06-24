FROM php:8.2-apache

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpng-dev libonig-dev libxml2-dev \
    npm nodejs \
    && docker-php-ext-install pdo pdo_mysql

# Installer Node.js v18 (plus stable)
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - && apt-get install -y nodejs

# Installer node-sass globalement
RUN npm install -g node-sass

# Active le module rewrite Apache
RUN a2enmod rewrite

# Définir le dossier public comme DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Modifier la config Apache pour le nouveau DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# Copier les fichiers du projet
COPY . /var/www/html/

# Fix des permissions pour éviter les erreurs 403
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Compiler SCSS (optionnel)
RUN node-sass public/scss/style.scss public/css/style.css || echo "Sass not found"

CMD ["apache2-foreground"]
