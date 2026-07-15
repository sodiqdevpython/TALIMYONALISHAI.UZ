# EduDirectionAI — PHP + Python (single container, called via exec)
FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive \
    PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        python3 python3-pip python3-venv \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zlib1g-dev \
        libonig-dev default-mysql-client \
        build-essential libgomp1 \
        ca-certificates curl unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mysqli gd zip mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Python ML deps (system-wide, single container so PHP `exec python` works)
COPY requirements.txt /tmp/requirements.txt
RUN pip3 install --no-cache-dir --break-system-packages -r /tmp/requirements.txt \
    || pip3 install --no-cache-dir -r /tmp/requirements.txt

# PHP tuning for long-running training jobs and Excel uploads
RUN { \
      echo "upload_max_filesize=200M"; \
      echo "post_max_size=200M"; \
      echo "memory_limit=2048M"; \
      echo "max_execution_time=0"; \
      echo "max_input_time=600"; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html

# Writable dirs
RUN mkdir -p /var/www/html/outputs /var/www/html/data \
             /var/www/html/outputs/models /var/www/html/outputs/schools \
    && chown -R www-data:www-data /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
# Windows CRLF -> LF, executable
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Alias `python` -> python3 so `exec('python ...')` in run.php works
RUN ln -sf /usr/bin/python3 /usr/local/bin/python

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
