# 2SCALE APP - Setup Guide

## Table of Contents
- [2SCALE APP - Setup Guide](#2scale-app---setup-guide)
  - [Table of Contents](#table-of-contents)
  - [Prerequisites](#prerequisites)
  - [Setup Steps](#setup-steps)
    - [1. Clone Repository](#1-clone-repository)
    - [2. Configure Environment](#2-configure-environment)
    - [3. Install Dependencies \& Setup Laravel](#3-install-dependencies--setup-laravel)
    - [4. Import Database Dump](#4-import-database-dump)
    - [5. Cache Database Records](#5-cache-database-records)
    - [6. Build Frontend Assets](#6-build-frontend-assets)
    - [7. Run \& Verify](#7-run--verify)

---

## Prerequisites
- PHP & Composer
- Node.js & NPM
- MySQL/PostgreSQL database

---

## Setup Steps

### 1. Clone Repository
```bash
git clone https://github.com/akvo/2scale.git
cd 2scale
```

### 2. Configure Environment
```bash
cp .env.example .env
```

Edit `.env` and update your database credentials:
```env
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Install Dependencies & Setup Laravel
```bash
# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Regenerate autoloader
composer dump-autoload

# Generate app key
php artisan key:generate

# Create database tables
php artisan migrate:fresh
```

### 4. Import Database Dump
```bash
mysql -u your_username -p your_database_name < path/to/2scale_dump.sql
```

### 5. Cache Database Records
```bash
php artisan 2scale:cache
```

### 6. Build Frontend Assets
```bash
npm install && npm run prod
```

### 7. Run & Verify
```bash
php artisan serve
```

Visit `http://localhost:8000` and test:
- Key features work correctly
- No errors

---
