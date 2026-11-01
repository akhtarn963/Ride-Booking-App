# RideLahore – Ride Booking Platform

A simplified ride-booking platform built as a technical assessment project. It provides a rider-facing interface for requesting rides, estimating fares, matching available drivers, and managing ride status.

## Tech Stack

### Backend
- PHP 8.2
- Laravel 12
- PostgreSQL 18
- Laravel Sanctum
- RESTful API
- Scramble (OpenAPI documentation)

### Frontend
- React 19
- TypeScript
- Vite
- Tailwind CSS 4

### Testing & Infrastructure
- PHPUnit
- Docker
- Docker Compose

## Features

### Rider Experience
- Select pickup and destination locations in Lahore.
- Choose Economy or Comfort vehicle categories.
- Get estimated fares and journey duration.
- Request a ride.
- View ride details, status, and assigned driver.
- Cancel eligible rides.
- Automatically refresh active ride status.

### Backend
- REST API for fare estimation and ride management.
- Haversine-based distance calculation with an approximate road-distance multiplier.
- Category-based fare calculation.
- Automatic driver matching.
- Driver availability management.
- Ride lifecycle management:
  - Requested
  - Accepted
  - In Progress
  - Completed
  - Cancelled
- Idempotent ride booking requests.
- Database transactions and row locking for driver assignment.
- PostgreSQL partial unique indexes to prevent duplicate active assignments.

## Architecture

The application consists of three main services:

| Service | Description |
|---|---|
| Frontend | React application served by Nginx |
| Backend | Laravel REST API |
| Database | PostgreSQL |

Docker Compose runs all three services.

## Project Structure

```text
ride-booking/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   └── Services/
├── database/
│   ├── migrations/
│   └── seeders/
├── docker/
│   └── entrypoint.sh
├── frontend/
│   ├── src/
│   └── Dockerfile
├── routes/
│   └── api.php
├── tests/
│   └── Feature/
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Requirements

For Docker-based setup:

- Docker Desktop
- Docker Compose

For local development:

- PHP 8.2+
- Composer
- Node.js 22+
- npm
- PostgreSQL 18+

## Getting Started with Docker

### 1. Clone the repository

```bash
git clone <YOUR_GITHUB_REPOSITORY_URL>
cd ride-booking
```

### 2. Create Docker environment configuration

Create a file named `.env.docker` in the project root.

Use the following values:

```env
APP_KEY=<YOUR_LARAVEL_APP_KEY>
DB_PASSWORD=<YOUR_DATABASE_PASSWORD>
```

Generate an application key if required:

```bash
php artisan key:generate --show
```

Use the generated key as `APP_KEY`. Choose a secure database password and use the same value for `DB_PASSWORD`.

### 3. Build and start the application

```bash
docker compose --env-file .env.docker up -d --build
```

Docker Compose starts the Laravel API, React frontend, and PostgreSQL database.

The backend entrypoint runs database migrations and seeds demo data.

### 4. Access the application

| Service | URL |
|---|---|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost:8000 |
| API Documentation | http://localhost:8000/docs/api |
| OpenAPI JSON | http://localhost:8000/docs/api.json |

### 5. Useful Docker commands

View running services:

```bash
docker compose ps
```

View application logs:

```bash
docker compose logs -f app
```

View all service logs:

```bash
docker compose logs -f
```

Stop services:

```bash
docker compose down
```

Stop services and remove the database volume (destructive):

```bash
docker compose down -v
```

## API Endpoints

Base URL:

```text
http://localhost:8000/api
```

| Method | Endpoint | Description |
|---|---|---|
| POST | `/fare-estimate` | Calculate estimated fare and duration |
| POST | `/rides` | Create a ride request |
| GET | `/rides/{id}` | Retrieve ride details |
| POST | `/rides/{id}/accept` | Accept a ride |
| POST | `/rides/{id}/start` | Start a ride |
| POST | `/rides/{id}/complete` | Complete a ride |
| POST | `/rides/{id}/cancel` | Cancel an eligible ride |

### Idempotency

Ride creation requires an `Idempotency-Key` request header.

Example:

```http
POST /api/rides
Idempotency-Key: unique-request-key
Content-Type: application/json
```

Repeated requests with the same key and payload return the existing ride instead of creating duplicates. Reusing the same key with a different payload is rejected.

### API Documentation

Interactive API documentation and the OpenAPI specification are available through Scramble:

- `/docs/api`
- `/docs/api.json`

## Testing

Run the backend test suite:

```bash
php artisan test
```

Run a specific feature test:

```bash
php artisan test --filter=RideBookingTest
```

The feature tests cover ride creation, idempotency, fare estimation, driver assignment, ride lifecycle transitions, and cancellation.

## Fare Calculation

The application uses a simplified fare estimation model.

- Distance is calculated using the Haversine formula.
- An approximate road-distance multiplier is applied.
- Economy and Comfort categories have different base fares and per-kilometre rates.
- Estimated journey duration uses an assumed average speed.

These estimates are for demonstration purposes and do not use live map traffic or routing data.

## Concurrency and Data Integrity

Driver assignment uses database transactions and row-level locking.

PostgreSQL `FOR UPDATE SKIP LOCKED` is used when selecting available drivers. Partial unique indexes enforce one active assignment per driver and one active assignment per ride.

## Current Limitations

- Location selection uses predefined Lahore locations rather than live geocoding.
- Fare and journey-time estimates are approximate.
- Authentication and role-based authorization are not yet implemented on the API endpoints.
- Driver actions are exposed through API endpoints; a dedicated driver application is outside the current scope.
- The application is intended as an assessment/demo implementation, not a production ride-hailing service.

## License

This project was developed for a technical assessment.
