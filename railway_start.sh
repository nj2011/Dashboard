#!/bin/bash

echo "=== HYPERION NEXUS STARTUP ==="

# Create necessary directories with proper permissions
mkdir -p /app/admin_data
mkdir -p /app/data/admin_data
mkdir -p /tmp/hyperion_admin_data

# Set permissions
chmod 755 /app/admin_data 2>/dev/null || true
chmod 755 /app/data/admin_data 2>/dev/null || true
chmod 755 /tmp/hyperion_admin_data 2>/dev/null || true

# Create default admin config if not exists
if [ ! -f "/app/admin_data/admin_config.json" ] && [ ! -f "/app/data/admin_data/admin_config.json" ]; then
    echo "Creating default admin credentials..."
    cat > /tmp/hyperion_admin_data/admin_config.json << 'EOF'
{
    "username": "admin",
    "password": "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi",
    "created_at": "2024-01-01 00:00:00"
}
EOF
    cp /tmp/hyperion_admin_data/admin_config.json /app/admin_data/ 2>/dev/null || true
    cp /tmp/hyperion_admin_data/admin_config.json /app/data/admin_data/ 2>/dev/null || true
fi

echo "Starting PHP server on port ${PORT:-8080}..."
php -S 0.0.0.0:${PORT:-8080} -t /app 2>&1