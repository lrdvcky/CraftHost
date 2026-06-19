# CraftHost

> A full-stack control panel for managing Minecraft servers — built with Laravel and Vue.js.

CraftHost is a web application that lets users provision, configure, and manage Minecraft game servers from a single dashboard. The backend exposes a REST API built on Laravel and secured with Laravel Sanctum token authentication, while the frontend is a Vue.js single-page application that consumes the API.

> **Note:** Replace the placeholder image paths below (`docs/screenshots/...`) with your own screenshots before publishing.

---

## Screenshots

| Dashboard | Server management |
| :---: | :---: |
| ![Dashboard](docs/screenshots/dashboard.png) | ![Server management](docs/screenshots/servers.png) |

| Login | Server console |
| :---: | :---: |
| ![Login](docs/screenshots/login.png) | ![Console](docs/screenshots/console.png) |

---

## Features

- **Server management** — create, start, stop, and configure Minecraft servers from a web dashboard.
- **Token-based authentication** — secure API access using Laravel Sanctum.
- **REST API backend** — clean separation between API and client, making the frontend and backend independently maintainable.
- **Single-page interface** — fast, reactive UI built with Vue.js.
- **Relational data model** — MySQL database with a structured schema (a dump is included in the repository).

---

## Tech Stack

**Backend**
- PHP 8.x
- Laravel
- Laravel Sanctum (API token authentication)
- MySQL

**Frontend**
- Vue.js 3
- Vite (dev server / bundler)
- JavaScript

**Other**
- Blade (server-side templating)

---

## Project Structure

```
CraftHost/
├── back/                # Laravel backend (REST API)
│   ├── app/             # Application logic, models, controllers
│   ├── database/        # Migrations, seeders
│   ├── routes/          # API routes
│   └── ...
├── front/               # Vue.js frontend (SPA)
│   ├── src/             # Components, views, API client
│   ├── public/
│   └── ...
├── crafthost_dump.sql   # MySQL database dump
└── .gitignore
```

---

## Getting Started

### Prerequisites

- PHP 8.x and Composer
- Node.js 18+ and npm
- MySQL 8.x

### Backend setup

```bash
cd back
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database credentials in `.env`, then import the included dump (or run migrations):

```bash
# Option A: import the provided dump
mysql -u root -p crafthost < ../crafthost_dump.sql

# Option B: run migrations
php artisan migrate
```

Start the backend:

```bash
php artisan serve
```

### Frontend setup

```bash
cd front
npm install
npm run dev
```

The frontend runs on `http://localhost:5173` by default and talks to the Laravel API.

---

## Authentication

CraftHost uses **Laravel Sanctum** for API authentication. The frontend obtains a token on login and includes it in the `Authorization: Bearer <token>` header on subsequent requests. This keeps the SPA and API decoupled while protecting all server-management endpoints.

---

## License

This project was developed as a custom solution for a client. Please contact the author before reusing the code.

---

## Author

**Danil** — Full-Stack Developer
GitHub: [@lrdvcky](https://github.com/lrdvcky)
