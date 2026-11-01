# RideLahore – System Architecture

## 1. Overview

RideLahore is a simplified ride-booking platform built as a take-home assessment. It allows riders to request rides, receive estimated fares and journey times, and get matched with available drivers.

The application uses a Laravel REST API, a React and TypeScript frontend, and PostgreSQL for persistent storage.

The current implementation focuses on the core ride-booking workflow, driver assignment, data consistency, and duplicate-request prevention.

## 2. Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| API | Laravel REST API |
| Frontend | React 19, TypeScript |
| Build Tool | Vite |
| Styling | Tailwind CSS 4 |
| Database | PostgreSQL 18 |
| API Documentation | Scramble / OpenAPI |
| Backend Testing | PHPUnit |
| Containerization | Docker and Docker Compose |

## 3. High-Level Architecture

The application follows a service-oriented Laravel architecture.

- **React frontend:** Collects ride details, requests fare estimates, submits bookings, and displays ride information.
- **REST API:** Validates incoming requests and coordinates application operations.
- **Application services:** Handle fare calculations, ride creation, idempotency, and driver matching.
- **PostgreSQL:** Stores riders, drivers, rides, assignments, and idempotency records.

The frontend communicates with the backend through HTTP JSON endpoints.

## 4. Database Design

### Riders

Stores rider information.

Important fields:
- `id`
- `name`
- `email`
- `phone`

A rider can have multiple rides.

### Drivers

Stores driver and vehicle information.

Important fields:
- `id`
- `name`
- `phone`
- `vehicle_category`
- `vehicle_model`
- `vehicle_number`
- `is_available`
- `latitude`
- `longitude`

Drivers are matched according to vehicle category and availability.

### Rides

Stores booking and journey information.

Important fields:
- `rider_id`
- `pickup_location`
- `pickup_latitude`
- `pickup_longitude`
- `destination_location`
- `destination_latitude`
- `destination_longitude`
- `vehicle_category`
- `estimated_fare`
- `estimated_duration_minutes`
- `final_fare`
- `status`
- Lifecycle timestamps

### Ride Assignments

Connects rides with drivers.

Important fields:
- `ride_id`
- `driver_id`
- `assigned_at`
- `released_at`

An assignment with a null `released_at` represents an active assignment.

### Idempotency Keys

Prevents duplicate rides when a booking request is retried.

Important fields:
- `rider_id`
- `key`
- `request_hash`
- `ride_id`

The combination of rider and idempotency key is unique.

## 5. Ride Booking Workflow

1. The rider selects pickup and destination locations.
2. The rider selects Economy or Comfort.
3. The frontend requests a fare estimate.
4. The backend validates the coordinates and vehicle category.
5. The fare estimation service calculates the estimated fare and duration.
6. The rider submits a booking request with an idempotency key.
7. The backend validates the rider and booking details.
8. The booking service checks the idempotency key.
9. A new ride is created with `requested` status.
10. The driver matching service searches for an available driver in the requested category.
11. If a suitable driver is found, an assignment is created and the driver becomes unavailable.
12. The API returns the ride details and assigned driver, if available.

If no driver is available, the ride remains in `requested` status.

## 6. Fare Estimation

The `FareEstimateService` uses the Haversine formula to calculate the geographical distance between pickup and destination coordinates.

An approximate road-distance multiplier of 1.25 is applied.

Current demo pricing:

| Vehicle | Base Fare | Per KM | Minimum Fare |
|---|---:|---:|---:|
| Economy | PKR 150 | PKR 55 | PKR 200 |
| Comfort | PKR 250 | PKR 75 | PKR 350 |

Estimated journey duration is calculated using an assumed average speed of 25 km/h, with an additional five-minute allowance.

These calculations are illustrative and do not use live maps, traffic, or routing data.

## 7. Driver Matching and Concurrency

Driver matching is handled by `DriverMatchingService`.

The service:

1. Starts a database transaction.
2. Locks the ride record.
3. Checks that the ride is still awaiting assignment.
4. Searches for an available driver matching the requested vehicle category.
5. Uses PostgreSQL `FOR UPDATE SKIP LOCKED` to avoid selecting a driver already locked by another transaction.
6. Creates a ride assignment.
7. Marks the selected driver unavailable.
8. Commits the transaction.

Database-level partial unique indexes provide additional protection:

- `unique_active_driver_assignment`: Prevents a driver from having more than one active assignment.
- `unique_active_ride_assignment`: Prevents a ride from having more than one active assignment.

The transaction and database constraints work together to protect assignment consistency.

## 8. Idempotency

Ride creation requires an `Idempotency-Key` request header.

The booking service generates a SHA-256 hash of the validated booking payload.

When a request is received:

- A new key creates a new booking.
- Reusing the same key with the same payload returns the existing ride.
- Reusing the same key with different booking data is rejected with a validation error.

This protects against duplicate bookings caused by network retries or repeated submissions.

## 9. Ride Lifecycle

The supported ride statuses are:

| Status | Description |
|---|---|
| `requested` | Ride has been requested |
| `accepted` | Driver has accepted the ride |
| `in_progress` | Journey has started |
| `completed` | Journey has finished |
| `cancelled` | Ride has been cancelled |

Supported transitions:

- Requested → Accepted
- Accepted → In Progress
- In Progress → Completed
- Requested → Cancelled
- Accepted → Cancelled

Lifecycle operations use database transactions and row locking to maintain consistent state changes.

When a ride is completed or cancelled, its active assignment is released and the driver is marked available again.

## 10. API Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/fare-estimate` | Calculate estimated fare and duration |
| POST | `/api/rides` | Create a ride |
| GET | `/api/rides/{id}` | Retrieve ride details |
| POST | `/api/rides/{id}/accept` | Accept a ride |
| POST | `/api/rides/{id}/start` | Start a journey |
| POST | `/api/rides/{id}/complete` | Complete a journey |
| POST | `/api/rides/{id}/cancel` | Cancel a ride |

Interactive API documentation is available at `/docs/api`, with the OpenAPI JSON document at `/docs/api.json`.

## 11. Testing Strategy

The backend feature tests cover:

- Ride booking validation
- Successful ride creation
- Duplicate booking prevention
- Idempotency key reuse with different payloads
- Automatic driver assignment
- Prevention of multiple active assignments
- Ride acceptance, start, and completion
- Cancellation and driver release
- Fare estimation
- Booking when no driver is available
- Invalid lifecycle transitions

The PHPUnit feature suite currently uses an in-memory SQLite database. PostgreSQL-specific concurrency behavior should also be verified using PostgreSQL integration tests.

## 12. Docker Setup

Docker Compose runs three services:

- **app:** Laravel API
- **frontend:** React production build served through Nginx
- **db:** PostgreSQL database

A named Docker volume persists PostgreSQL data.

The Laravel container waits for PostgreSQL, runs migrations and seeders, clears application caches, and starts the API server.

## 13. Current Scope and Future Improvements

The current implementation is intentionally simplified for the assessment.

Potential improvements include:

- Rider and driver authentication and authorization
- Driver-facing application and acceptance workflow
- Real map and routing integration
- Live driver location tracking
- Real-time ride status updates using WebSockets
- More advanced driver selection based on distance
- Production-grade deployment using PHP-FPM and Nginx
- PostgreSQL concurrency integration tests
- Payment gateway integration and ride history

## 14. Design Considerations

The implementation separates controllers from business logic using dedicated services. Database transactions, row-level locking, unique constraints, and idempotency records help maintain data integrity during booking and assignment operations.

The architecture provides a foundation that can be extended with authentication, real-time communication, mapping, and production infrastructure.

### PostgreSQL Integration Tests

A separate PostgreSQL integration test suite verifies the database-level
constraints that prevent:

- A driver from having multiple active ride assignments.
- A ride from having multiple active driver assignments.

These tests run against a dedicated `ride_booking_test` database using
`phpunit.pgsql.xml`. The SQLite feature suite and PostgreSQL integration
suite are executed separately.