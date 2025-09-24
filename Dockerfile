# Usa imagem oficial PHP com Apache
FROM php:8.2-apache

# Instala extensões necessárias (ex: pdo_pgsql para Supabase/Postgres)
RUN docker-php-ext-install pdo pdo_pgsql

# Copia os arquivos para a pasta do Apache
COPY . /var/www/html/

# Define a pasta public como DocumentRoot
WORKDIR /var/www/html

# Configura o Apache para usar a pasta public como raiz
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf

# Ativa mod_rewrite para rotas amigáveis
RUN a2enmod rewrite

# Expõe porta 80
EXPOSE 80
