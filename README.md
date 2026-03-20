# UserUpload API

REST API for image upload management with JWT authentication, OTP email verification, and automatic image compression.

## Tech Stack

- **PHP 8.2+** / **Laravel 12**
- **PostgreSQL 15** - primary database
- **Redis 7** - cache (OTP codes, rate limiting)
- **Nginx** - reverse proxy
- **JWT Auth** (`tymon/jwt-auth`) - token-based authentication
- **GD** - image compression (JPEG/PNG)

## Architecture

```
Controller (thin) -> Service (business logic) -> Model (data)
     |                    |
 FormRequest          Interface
  + toDTO()           (Contract)
```

- **Contracts** - service interfaces for DI and testability (`app/Contracts/`)
- **DTOs** - typed input/output objects (`app/DTOs/`)
- **Events** - domain events for extensibility (`app/Events/`)
- **Policies** - authorization via Laravel Policy (`app/Policies/`)
- **Jobs** - async image compression via queue (`app/Jobs/`)

## Quick Start

### Requirements

- Docker & Docker Compose

### Setup

1. Clone the repository and copy the environment file:

```bash
cp .env.example .env
```

2. Configure `.env` with your database credentials and mail settings (Gmail SMTP).

3. Start the containers:

```bash
docker compose up -d
```

4. Run migrations:

```bash
docker compose exec user-upload-app php artisan migrate
```

5. Generate JWT secret (if not set):

```bash
docker compose exec user-upload-app php artisan jwt:secret
```

The API is available at `http://localhost:8000/api`.

## API Endpoints

### Auth

| Method | Endpoint | Description | Rate Limit |
|--------|----------|-------------|------------|
| POST | `/api/auth/register` | Register + send OTP to email | 5/min |
| POST | `/api/auth/verify-otp` | Verify OTP code | 5/min |
| POST | `/api/auth/login` | Login, receive JWT token | 5/min |

### Images (JWT required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/images` | List user images (filterable, paginated) |
| POST | `/api/images` | Upload image (JPEG/PNG, max 5MB) |
| GET | `/api/images/{id}` | View image details |
| DELETE | `/api/images/{id}` | Delete image |

### Auth Flow

```
Register (name, email, password)
    -> OTP code sent to email
        -> Verify OTP (email, code)
            -> Login (email, password)
                -> JWT token
                    -> Use token in Authorization: Bearer <token>
```

### Image Filters (GET /api/images)

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | int (1-100) | Items per page (default: 20) |
| `sort_by` | string | `created_at`, `original_name`, `size` |
| `sort_direction` | string | `asc`, `desc` |
| `original_name` | string | Search by filename |
| `mime_type` | string | `image/jpeg` or `image/png` |
| `date_from` | date | Filter from date (Y-m-d) |
| `date_to` | date | Filter to date (Y-m-d) |

## Testing

```bash
# Create test database
psql -U upload_user -d upload -c "CREATE DATABASE upload_test"

# Run all tests
php artisan test

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature
```

Tests use PostgreSQL (`upload_test` database) and array cache driver.

## Postman Collection

Import `postman_collection.json` from the project root into Postman to get the full API collection.

## Project Structure

```
app/
  Contracts/          # Service interfaces
  DTOs/
    Auth/             # RegisterDTO, LoginDTO, LoginResultDTO, OtpResultDTO
    Image/            # ImageFilterDTO
  Events/             # UserRegistered, OtpVerified, UserLoggedIn, ImageUploaded, ImageDeleted
  Exceptions/         # Domain exceptions (InvalidOtp, UserNotFound, etc.)
  Http/
    Controllers/Api/  # AuthController, ImageController
    Requests/         # FormRequests with toDTO() methods
    Resources/        # API resources (UserResource, ImageResource)
  Jobs/               # CompressImageJob (async image compression)
  Mail/               # OtpMail
  Models/             # User, Image, File
  Policies/           # ImagePolicy
  Providers/          # Service bindings
  Services/           # AuthService, ImageService, FileService
tests/
  Unit/Services/      # Isolated service tests with mocks
  Feature/            # HTTP endpoint tests
```
