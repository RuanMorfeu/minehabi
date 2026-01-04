#!/bin/bash

# Get current date for backup file name
DATE=$(date +"%Y%m%d_%H%M%S")

# Database credentials from .env file
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Create backups directory if it doesn't exist
BACKUP_DIR="database/backups"
mkdir -p $BACKUP_DIR

# Backup file name
BACKUP_FILE="${BACKUP_DIR}/backup_${DB_DATABASE}_${DATE}.sql"

# Create the backup
mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE"

# Check if backup was successful
if [ $? -eq 0 ]; then
    echo "Database backup created successfully: $BACKUP_FILE"
    
    # Keep only last 5 backups
    cd "$BACKUP_DIR"
    ls -t *.sql | tail -n +6 | xargs -r rm
    echo "Old backups cleaned up. Keeping only 5 most recent backups."
else
    echo "Error creating database backup"
    exit 1
fi
