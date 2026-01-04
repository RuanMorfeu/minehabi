#!/bin/bash

# Load environment variables from .env file
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}')
fi

# Database credentials from environment variables
DB_HOST=${DB_HOST:-"localhost"}
DB_PORT=${DB_PORT:-"3306"}
DB_DATABASE=${DB_DATABASE:-"database"}
DB_USERNAME=${DB_USERNAME:-"root"}
DB_PASSWORD=${DB_PASSWORD}

# Check if backup.sql exists
if [ ! -f "backup.sql" ]; then
    echo "Error: backup.sql file not found!"
    exit 1
fi

# Import the database
echo "Starting database import..."
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < backup.sql

if [ $? -eq 0 ]; then
    echo "Database import completed successfully!"
else
    echo "Error: Database import failed!"
    exit 1
fi
