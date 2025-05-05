# **Selise Management App**

This project consists of a Laravel-based backend and a Vue.js frontend.

## **Project Structure**

# **Selise Management App**

This project consists of a Laravel-based backend and a Vue.js frontend.

## **Project Structure**

```tsx
source/
├── backend-api/   # Laravel Backend
└── frontend/      # Vue.js Frontend
```

## **Prerequisites**

- PHP >= 8.1
- Composer
- MySQL
- Node.js >= 16
- npm or yarn

**1. Backend Setup (Laravel)**

```tsx
cd source/backend-api

composer install

cp .env.example .env

DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

php artisan key:generate
php artisan migrate --seed

composer run dev

```

**2. Frontend Setup (Vue.js)**

```tsx
cd source/frontend

npm install
# or
yarn install

npm run dev
# or
yarn dev
```

Ensure the backend is running before using the frontend.

## **Common Issues**

- If migrations fail, ensure your database exists and credentials are correct.
- CORS issues? Check Laravel’s CORS middleware configuration.
