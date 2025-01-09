#!/bin/bash

# Stop containers
./vendor/bin/sail down

# Start and rebuild containers
./vendor/bin/sail up -d --build

# Clear Laravel cache and configurations
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan route:clear

# Build frontend assets
NODE_ENV=production ./vendor/bin/sail npm run build

# Start ngrok
ngrok http --url=smart-turkey-crisp.ngrok-free.app 80