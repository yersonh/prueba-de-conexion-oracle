FROM php:8.1-apache-bookworm

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    libaio1 \
    libaio-dev \
    wget \
    unzip \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Crear directorio para Oracle Instant Client
RUN mkdir -p /opt/oracle

# Descargar Oracle Instant Client (versión 21.9)
RUN cd /opt/oracle && \
    wget https://download.oracle.com/otn_software/linux/instantclient/219000/instantclient-basic-linux.x64-21.9.0.0.0dbru.zip || \
    (echo "Failed to download Oracle Instant Client" && exit 1) && \
    unzip -q instantclient-basic-linux.x64-21.9.0.0.0dbru.zip && \
    rm instantclient-basic-linux.x64-21.9.0.0.0dbru.zip && \
    ls -la /opt/oracle/

# Configurar variables de entorno de Oracle
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_9
ENV ORACLE_HOME=/opt/oracle/instantclient_21_9

# Instalar extensión oci8
RUN docker-php-ext-configure oci8 --with-oci8=instantclient,/opt/oracle/instantclient_21_9 && \
    docker-php-ext-install oci8

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Copiar proyecto
COPY . /var/www/html

# Establecer permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    mkdir -p /var/www/html/uploads && \
    chmod -R 777 /var/www/html/uploads

WORKDIR /var/www/html

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]