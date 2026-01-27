#!/bin/bash
set -e

echo "=========================================="
echo "LW Site Manager - Dev Container Setup"
echo "=========================================="

# Install Composer dependencies
echo ""
echo ">> Installing Composer dependencies..."
composer install --no-interaction

# Wait for MySQL to be ready
echo ""
echo ">> Waiting for MySQL..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --skip-ssl --silent 2>/dev/null; do
    sleep 1
done
echo ">> MySQL is ready!"

# Install WordPress test suite
echo ""
echo ">> Installing WordPress test suite..."
bash tests/bin/install-wp-tests.sh "$DB_NAME" "$DB_USER" "$DB_PASSWORD" "$DB_HOST" latest true

echo ""
echo "=========================================="
echo "Setup complete!"
echo "=========================================="
echo ""
echo "Run tests:"
echo "  composer test              # Unit tests"
echo "  composer test:integration  # Integration tests (with WordPress)"
echo ""
