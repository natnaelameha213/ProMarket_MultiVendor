# ProMarket Multi-Vendor

> Multi-vendor e-commerce marketplace

**Live demo:** https://promarket-multivendor.freedev.app

## Screenshots

![Home](docs/screenshots/home.png)
![Shop](docs/screenshots/shop.png)
![Admin Dashboard](docs/screenshots/admin-dashboard.png)

## Features

- Storefront: search, filters, product pages with gallery and reviews, cart, checkout (COD, bank transfer, mobile money), coupons
- Customer account: orders, wishlist, profile
- Vendor dashboard: product CRUD, orders, reviews (admin approval required to register)
- Admin dashboard: live stats and charts, vendor approval, products, categories, customers, coupons, review moderation, sales reports, settings
- CSRF tokens, bcrypt, PDO prepared statements, validated uploads

## Tech stack

PHP 8 · MySQL (PDO) · Bootstrap 5 · Chart.js

## Demo login (local install)

| Role | Email / user | Password |
|---|---|---|
| Admin | `admin@promarket.local` | `Admin123!` |
| Vendor | `abebe@techhub.et` | `Admin123!` |
| Customer | `customer1@email.com` | `Admin123!` |

These are the default credentials of the seed data. The live demo's admin password is private.

## Run locally (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\ProMarket_MultiVendor`
2. Start **Apache** and **MySQL** in XAMPP
3. Open phpMyAdmin → **Import** → `database/promarket_multivendor.sql`
4. Check the database settings in `config/config.php` (default: host `localhost`, user `root`, empty password)
5. Open `http://localhost/ProMarket_MultiVendor/`

### Deploying to shared hosting

Create an empty database in your hosting panel, delete the `CREATE DATABASE` / `USE` / `DROP DATABASE` lines at the top of `database/promarket_multivendor.sql`, import it, then put the host's database name, user and password in `config/config.php`.

## Notes

Portfolio / demo project. Before real production use add HTTPS, rate limiting, audit logs, backups and environment-based configuration.

## Author

Built by [natnaelameha213](https://github.com/natnaelameha213).
