# Use official PHP image
FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Copy your PHP files
COPY . .

# Expose the port Render will use
EXPOSE 10000

# Start PHP's built-in server
CMD ["php", "-S", "0.0.0.0:10000", "index.php"]
