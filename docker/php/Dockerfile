FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    nodejs \
    npm \
    unzip \
    zip \
    ghostscript \
    imagemagick \
    libzip-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libicu-dev \
    libxml2-dev \
    libsqlite3-dev \
    sqlite3 \
    libexif-dev \
    && rm -rf /var/lib/apt/lists/*

# GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install gd

# TEK TEK KUR (çok önemli)
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install exif
RUN docker-php-ext-install intl
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install opcache
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_sqlite
RUN docker-php-ext-install zip

# ImageMagick güvenlik politikasını gevşet: PDF/AI/EPS/PS dönüşümüne izin ver
RUN find /etc/ImageMagick-* -name "policy.xml" -exec \
    sed -i \
        -e 's|<policy domain="coder" rights="none" pattern="PDF".*/>|<policy domain="coder" rights="read\|write" pattern="PDF"/>|g' \
        -e 's|<policy domain="coder" rights="none" pattern="PS".*/>|<policy domain="coder" rights="read\|write" pattern="PS"/>|g' \
        -e 's|<policy domain="coder" rights="none" pattern="PS2".*/>|<policy domain="coder" rights="read\|write" pattern="PS2"/>|g' \
        -e 's|<policy domain="coder" rights="none" pattern="PS3".*/>|<policy domain="coder" rights="read\|write" pattern="PS3"/>|g' \
        -e 's|<policy domain="coder" rights="none" pattern="EPS".*/>|<policy domain="coder" rights="read\|write" pattern="EPS"/>|g' \
        -e 's|<policy domain="coder" rights="none" pattern="XPS".*/>|<policy domain="coder" rights="read\|write" pattern="XPS"/>|g' \
    {} + 2>/dev/null || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zzz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zzz-opcache.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
