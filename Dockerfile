# Use the official PHP image for CLI
FROM php:8.3

# Set the working directory in the container
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install pcntl extension
RUN docker-php-ext-install pcntl

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy only the necessary files to the container
COPY composer.json composer.lock /app/
COPY src /app/src
COPY example /app/example

# Install project dependencies
RUN composer install --ignore-platform-reqs --optimize-autoloader

# Start the application
CMD ["php", "./example/test.php"]