# Usa la imagen base de Ubuntu 20.04
FROM ubuntu:20.04

# Configura la zona horaria
RUN ln -sf /usr/share/zoneinfo/America/Lima /etc/localtime && \
    echo "America/Lima" > /etc/timezone && \
    apt-get update && \
    apt-get install -y tzdata

# Instala Apache, PHP 7.4 y las extensiones necesarias para MySQL
RUN apt-get update && apt-get install -y \
    apache2 \
    php7.4 \
    libapache2-mod-php7.4 \
    php7.4-mysql \
    php7.4-zip \
    unzip \
    && apt-get clean

RUN a2enmod php7.4
# Habilita el módulo mod_rewrite de Apache
RUN a2enmod rewrite

# Crea los directorios necesarios
RUN mkdir -p /var/www/html /webapps/sisacad/

# Copia los archivos de la aplicación al directorio correspondiente
COPY . /webapps/sisacad/

# Configura el archivo de sitios disponibles para Apache
RUN echo 'Alias /sisacad "/webapps/sisacad"\n \
<Directory "/webapps/sisacad">\n \
    Options Indexes FollowSymLinks MultiViews\n \
    AllowOverride All\n \
    Require all granted\n \
</Directory>\n \
<VirtualHost *:80>\n \
    ServerName webapp01.com\n \
    ServerAdmin webmaster@localhost\n \
    DocumentRoot /webapps/sisacad\n \
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n \
    ErrorLog ${APACHE_LOG_DIR}/error.log\n \
</VirtualHost>' > /etc/apache2/sites-available/sisacad.conf

# Habilita el sitio configurado y desactiva el sitio predeterminado
RUN a2ensite sisacad.conf

# Ajusta permisos para el directorio de la aplicación
RUN chown -R www-data:www-data /webapps/sisacad/

# Expone el puerto 80 para que Docker lo utilice
EXPOSE 80

# Inicia Apache en el contenedor
CMD ["apachectl", "-D", "FOREGROUND"]
 
