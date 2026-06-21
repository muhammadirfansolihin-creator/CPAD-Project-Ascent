# CampusEats — Pre-Order & Pickup Platform for Campus F&B

Group: Ascent | SCSM2223 Cross-Platform Application Development | Semester II 2025/2026

## Quick Start
Start All Laragon process

### Backend (First Terminal - Laragon)
```bash
cd backend
composer install
# Configure config/db.php with your MySQL credentials
# Import db/schema.sql into MySQL
php -S localhost:8000 -t public
```

### Frontend (Second Terminal - Laragon)
```bash
cd frontend
npm install
npm run dev
```

## Demo Accounts (after seeding schema.sql)
| Role    | Email                     | Password    |
|---------|---------------------------|-------------|
| Admin   | admin@campuseats.my       | password    |
| Vendor  | vendor@campuseats.my      | password    |
| Student | student@campuseats.my     | password    |

## Stack
- **Backend:** PHP 8.1 · Slim 4 · PDO · MySQL · firebase/php-jwt
- **Frontend:** Vue 3 · Vite · Pinia · Vue Router · Axios
