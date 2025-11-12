#!/bin/bash
# Build script for Vercel deployment

# Build assets
npm run build

# Create production .env file
cat > .env << EOF
APP_NAME=Onlifin
APP_ENV=production
APP_KEY=base64:wkXphBGDDSVU8qDbnqa4ICt6G1c6IsaxigBqFzAzc9Y=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://onlifin.vercel.app
DB_CONNECTION=sqlite
DB_DATABASE=/var/task/database/database.sqlite
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
EOF

echo "Build completed successfully!"