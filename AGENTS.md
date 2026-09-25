# Card Management System

## Overview
A Laravel-based wedding invitation card management system with QR code verification, WhatsApp integration, and guest attendance tracking.

## Tech Stack
- **Backend:** Laravel 12 (PHP 8.4)
- **Database:** SQLite (development) / MySQL (production)
- **Frontend:** Blade templates + Tailwind CSS (CDN)
- **QR Codes:** qrcode.js (client-side generation)
- **Scanner:** jsQR.js (client-side camera scanning)
- **WhatsApp:** Meta Cloud API (interactive messages)

## Setup Commands
```bash
# Install dependencies (needs composer)
composer install

# Copy env and generate key
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed test user
php artisan db:seed

# Create storage symlink
php artisan storage:link

# Start development server
php artisan serve --port=8000
```

## Default Login Credentials
- Email: admin@cardms.com
- Password: password123

## Key Routes
- `/dashboard` - Main dashboard
- `/events` - Event management (CRUD)
- `/events/{id}/guests` - Guest management with import/export
- `/events/{id}/invitations` - WhatsApp invitation management
- `/events/{id}/scanner` - QR code scanner for check-in
- `/webhook/whatsapp` - WhatsApp webhook endpoint

## Database Schema
- `users` - System users
- `events` - Event details + card template settings
- `guests` - Guest records with unique IDs
- `invitations` - Invitation status, RSVP, and attendance tracking
- `activity_logs` - Audit trail

## WhatsApp Configuration
Set these in `.env`:
```
WHATSAPP_API_URL=https://graph.facebook.com/v17.0
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_verify_token
WHATSAPP_APP_SECRET=your_meta_app_secret
```
The verify token is only used for the GET handshake. `WHATSAPP_APP_SECRET`
(Meta App Dashboard → Settings → Basic) is what validates the
`X-Hub-Signature-256` header on incoming POSTs.

## Switching to MySQL (Production)
Update `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=card_management
DB_USERNAME=root
DB_PASSWORD=your_password
```
Then run: `php artisan migrate:fresh --seed`

## Excel Import Format
CSV/XLSX with headers: Full Name, Phone Number, Amount Contributed, Card Type, Email, Table Number, Category, Notes
