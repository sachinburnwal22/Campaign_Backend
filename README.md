# ShopReach CRM - Campaign Engine Backend

A high-performance campaign orchestrator and customer management REST API built with **Laravel**. It features dynamic database driver support, Google Gemini AI integration, real-time Twilio SMS/WhatsApp delivery, and beautiful responsive HTML email dispatches.

## 🚀 Key Features

* **🤖 Gemini AI Strategy Engine**: Receives natural language marketing prompts, compiles them into structured JSON segments, generates copy drafts, and recommends channels.
  - *Fallback Parser*: Has a built-in rule-based regex parser that handles requests offline if the API key is missing.
* **📱 Live Twilio Delivery (SMS & WhatsApp)**: Integrates directly with Twilio's HTTP REST endpoints:
  - Formats phone numbers dynamically (adding country codes, e.g., `+91` prefix for Indian mobiles).
  - Automatically wraps numbers with the `whatsapp:` namespace.
* **✉️ Premium HTML Email Deliveries**: Sends beautifully formatted, responsive HTML template emails (instead of plain text) featuring dark mode layouts, glowing gradient logo branding, and styled Call-To-Action buttons.
* **⚙️ Dynamic Database Driver Support**: Dialect-agnostic SQL queries that resolve at runtime using `DB::connection()->getDriverName()`. Supports:
  - MySQL / MariaDB (utilizes `DATE_FORMAT` and decimal cast precision)
  - PostgreSQL (utilizes `to_char` and numeric cast precision)
  - SQLite (utilizes `strftime`)
* **⚡ Instant Queue Runner**: Fully configured with `QUEUE_CONNECTION=sync` for real-time campaign execution and callback dispatches during HTTP requests.

## 🛠️ Technology Stack

* **Framework**: Laravel 12+
* **PHP Version**: 8.2+
* **Database**: MySQL (local port 3306), fully migration-ready
* **Services**: Twilio REST API, Google Gemini API, Laravel Mailer (SMTP)

## 📦 Getting Started

### Prerequisites
Make sure you have PHP 8.2+, Composer, and MySQL installed and running on your host machine.

### Installation & Environment Setup
1. Navigate into the backend directory:
   ```bash
   cd backend
   ```
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Copy the environment template:
   ```bash
   copy .env.example .env
   ```
4. Generate the Laravel application key:
   ```bash
   php artisan key:generate
   ```

### Database Setup
1. Create a database named `shopreach_crm` on your local MySQL server (port 3306).
2. Configure your database details inside the `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=shopreach_crm
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Run the migrations and seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Third-Party Service Credentials
Configure your service keys in your `.env` file to enable live deliveries:
```env
# Google Gemini
GEMINI_API_KEY=your_gemini_api_key

# Twilio SMS / WhatsApp
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_PHONE_NUMBER=your_twilio_phone_number
TWILIO_WHATSAPP_NUMBER=your_twilio_whatsapp_number

# Gmail SMTP Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail_address@gmail.com
MAIL_PASSWORD=your_gmail_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_gmail_address@gmail.com
MAIL_FROM_NAME="ShopReach AI"
```

### Running Locally
Start the PHP built-in development server:
```bash
php artisan serve
```
The API server will run at [http://127.0.0.1:8000](http://127.0.0.1:8000).
