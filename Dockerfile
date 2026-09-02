# Use the official PHP image for CLI
FROM php:8.5-cli

# Set the working directory in the container
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install the extensions this package declares. ext-posix is already enabled in the
# official image, ext-pcntl has to be built in.
RUN docker-php-ext-install pcntl

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Copy only the necessary files to the container.
# This is a library, so it deliberately ships no composer.lock.
COPY composer.json /app/
COPY src /app/src
COPY example /app/example

# Install project dependencies. ext-pcntl and ext-posix are present, so the platform
# requirements are checked rather than ignored.
RUN composer update --no-dev --optimize-autoloader --no-interaction --no-progress

# Start the application
CMD ["php", "./example/example.php"]
