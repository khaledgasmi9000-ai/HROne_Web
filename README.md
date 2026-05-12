# HROne — Human Resources Management System

A full-featured HR platform built with **Symfony 6.4**, covering the entire employee lifecycle: recruitment, onboarding, training, events, leave management, and community engagement.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [User Roles](#user-roles)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Environment Variables](#environment-variables)
- [Running the App](#running-the-app)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)
- [License](#license)

---

## Features

### For HR Staff (RH)
- **Event Management** — Create events with activities, manage registrations and waiting lists
- **Training Management** — Schedule formations, track participants, generate certificates (PDF)
- **Leave Approvals** — Review and approve employee leave requests
- **Candidate Pipeline** — Post job offers, track applications and interviews
- **Employee Administration** — Manage employee profiles, departments, and tools/equipment
- **Statistics Dashboard** — Overview charts and reports, exportable to Excel

### For Employees
- **Event Registration** — Browse and register for company events; automatic waitlist if full
- **Training Enrollment** — Sign up for formations and download completion certificates
- **Leave Requests** — Submit and track leave requests
- **Community Feed** — Posts, comments, and social interaction with colleagues
- **Activity Watch** — Log and track work sessions

### For Candidates
- **Job Board** — Browse open positions and submit applications
- **Application Tracking** — Follow the status of submitted candidatures

### Automated Features
- QR code generation on event tickets
- PDF ticket and certificate downloads
- Automatic waitlist promotion (FIFO) when a spot opens up
- Email notifications for confirmations, waitlist placement, and promotions
- AI chatbot assistant on event pages (keyword-based, no external API required)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Symfony 6.4 |
| Language | PHP 8.1+ |
| Database | PostgreSQL 16 (via Docker) |
| ORM | Doctrine ORM 3.6 |
| Templating | Twig + Stimulus.js |
| Charts | Chart.js (symfony/ux-chartjs) |
| Admin Panel | EasyAdmin 4.10 |
| PDF Generation | nucleos/dompdf-bundle |
| QR Codes | endroid/qr-code-bundle |
| File Uploads | VichUploaderBundle |
| Image Processing | LiipImagineBundle |
| Pagination | KnpPaginatorBundle |
| Spreadsheet Export | PHPOffice/PhpSpreadsheet |
| Email | Symfony Mailer (SMTP) |
| Async Tasks | Symfony Messenger |
| Dev Server | Symfony CLI |

---

## User Roles

| Role | Access |
|---|---|
| `ROLE_ADMIN` | Full access to all modules |
| `ROLE_RH` | HR management: events, formations, candidates, employees, leave |
| `ROLE_EMPLOYEE` | Events, formations, leave requests, community, activity watch |
| `ROLE_CANDIDAT` | Job offers and personal candidature tracking |

Role hierarchy: `ROLE_ADMIN` → `ROLE_RH` → `ROLE_EMPLOYEE` / `ROLE_CANDIDAT`

---

## Prerequisites

- PHP 8.1 or higher (with `ext-ctype`, `ext-iconv`)
- [Composer](https://getcomposer.org/)
- [Docker & Docker Compose](https://docs.docker.com/get-docker/)
- [Symfony CLI](https://symfony.com/download) (recommended)

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-username/hrone.git
cd hrone

# 2. Install PHP dependencies
composer install

# 3. Set up environment variables
cp .env.local.example .env.local
# Edit .env.local with your database credentials and mailer settings

# 4. Start the PostgreSQL database via Docker
docker compose up -d

# 5. Create the database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Install frontend assets
php bin/console importmap:install
php bin/console assets:install
```

---

## Environment Variables

Copy `.env.local.example` to `.env.local` and fill in the values:

```dotenv
# Database (matches compose.yaml defaults)
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/hr_one?serverVersion=16&charset=utf8"

# Mailer — use null://null to disable emails locally
MAILER_DSN=smtp://user:pass@smtp.example.com:587?encryption=tls
MAILER_FROM_EMAIL=hrone@yourcompany.com
MAILER_FROM_NAME="HR One"

# AI Chatbot (optional — Groq API)
GROQ_API_KEY=your_groq_api_key_here
GROQ_MODEL=llama-3.1-8b-instant
```

> The chatbot works fully offline without a Groq API key using built-in keyword matching.

---

## Running the App

```bash
# Start the development server
symfony server:start

# Or with PHP's built-in server
php -S localhost:8000 -t public/
```

Then open [http://localhost:8000](http://localhost:8000) in your browser.

| URL | Description |
|---|---|
| `/login` | Login page |
| `/dashboard` | User dashboard |
| `/rh/evenements` | HR — Event management |
| `/evenements` | Employee — Event listing |
| `/formations` | Employee — Training catalog |
| `/admin` | EasyAdmin panel (ROLE_RH+) |

---

## Project Structure

```
hrone/
├── config/
│   ├── packages/          # Bundle configuration (security, doctrine, mailer…)
│   └── services.yaml      # Service definitions
├── migrations/            # Doctrine database migrations
├── public/
│   ├── styles/            # CSS per module
│   ├── js/                # Vanilla JS per module
│   └── assets/            # Navbar and shared assets
├── src/
│   ├── Controller/        # Route controllers (RH + Employee + API)
│   ├── Entity/            # Doctrine entities
│   ├── Form/              # Symfony form types
│   ├── Repository/        # Custom Doctrine repositories
│   ├── Security/          # Login authenticator
│   └── Service/           # Business logic (email, QR code, waitlist, chatbot…)
├── templates/
│   ├── navbarRH/          # HR-side Twig templates
│   ├── Topnavbar/         # Employee-side Twig templates
│   ├── emails/            # Email templates
│   └── partials/          # Reusable components (chatbot, etc.)
├── compose.yaml           # Docker Compose (PostgreSQL)
└── composer.json
```

---

## Screenshots

> Add screenshots here once the app is deployed or running locally.

---

## License

This project is proprietary. All rights reserved.
