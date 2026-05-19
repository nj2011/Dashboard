#!/bin/bash

# Create data directory in volume
mkdir -p /app/data/admin_data
mkdir -p /app/admin_data
chmod 755 /app/data/admin_data
chmod 755 /app/admin_data

# Start PHP server
php -S 0.0.0.0:${PORT:-8080} -t /app