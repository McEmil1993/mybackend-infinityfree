# Laravel REST API

JSON-only REST API built with Laravel 13, PHP 8.3, MySQL, and Laravel Sanctum.
It provides user registration, login, logout, authenticated user listing, profile
lookup, and password changes. There are no web pages: visiting `/` returns a JSON
`404 Not Found` response.

## Features

- JSON responses for successful requests and errors
- Sanctum bearer-token authentication
- User registration and login
- Protected user list and current-user profile
- Password change with current-password verification
- Logout and token revocation
- MySQL database
- Docker development environment on `http://localhost:9001`
- Automated feature tests
- VS Code REST Client request collection

## Requirements

Choose one development setup:

- Docker Engine with Docker Compose, or
- PHP 8.3 with Composer and the required PHP extensions

The local PHP extensions used by this project include:

```text
bcmath, curl, fileinfo, gd, intl, mbstring, mysqli, openssl,
pdo_mysql, redis, xml, zip
```

The Docker image also includes SQLite only for isolated in-memory automated tests.
The running application uses MySQL.

Node.js is not required to run this API.

## Project Layout

```text
dockerfiles/
├── Dockerfile
├── docker-compose.yml
├── data/
│   └── mybackend/       # Laravel application
└── restclient/
    └── api-test.http    # VS Code REST Client requests
```

## Docker Setup

Run these commands from the repository root, the directory containing
`docker-compose.yml`.

### 1. Build the image

```bash
docker compose build
```

### 2. Install PHP dependencies

```bash
docker compose run --rm -w /data/mybackend ubuntu composer install
```

### 3. Create the environment file

Skip this command if `data/mybackend/.env` already exists.

```bash
docker compose run --rm -w /data/mybackend ubuntu sh -c "cp .env.example .env"
```

### 4. Generate the application key

```bash
docker compose run --rm -w /data/mybackend ubuntu php artisan key:generate
```

### 5. Configure MySQL

Create an empty MySQL database, then configure `data/mybackend/.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=YOUR_MYSQL_HOST
DB_PORT=3306
DB_DATABASE=laravel_api
DB_USERNAME=YOUR_MYSQL_USER
DB_PASSWORD=YOUR_MYSQL_PASSWORD
```

When MySQL runs on the host machine, `127.0.0.1` inside the container points to
the container itself. Use a host address reachable from Docker, such as
`host.docker.internal` on Docker Desktop, or the appropriate host/gateway IP on
Linux. If MySQL is remote, use its server IP address or hostname.

### 6. Run the database migrations

```bash
docker compose run --rm -w /data/mybackend ubuntu php artisan migrate
```

### 7. Start the API

```bash
docker compose up -d
```

The API is now available at:

```text
http://localhost:9001
```

Useful Docker commands:

```bash
# View application output
docker compose logs -f ubuntu

# Open a shell in the running container
docker compose exec ubuntu bash

# Stop and remove the container
docker compose down

# Rebuild after changing the Dockerfile
docker compose up -d --build
```

The local `data/` directory is mounted at `/data` inside the container. Changes
made to the Laravel source code are immediately visible inside the container.

## Local Linux Setup

The following package command is for Ubuntu 24.04, which provides PHP 8.3 in its
official repositories.

### 1. Install PHP, extensions, and tools

```bash
sudo apt-get update
sudo apt-get install -y \
  php8.3-cli php8.3-bcmath php8.3-curl php8.3-gd php8.3-intl \
  php8.3-mbstring php8.3-mysql php8.3-redis \
  php8.3-xml php8.3-zip curl unzip
```

Install Composer if it is not already available, then verify the installation:

```bash
php --version
composer --version
```

### 2. Configure the Laravel project

From the repository root:

```bash
cd data/mybackend
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database and update the `DB_*` values in `.env`, then run:

```bash
php artisan migrate
```

If `.env` already exists, do not overwrite it.

### 3. Start the local server

```bash
php artisan serve --host=127.0.0.1 --port=9001
```

Open `http://localhost:9001` to confirm that the API returns a JSON 404 response.

## Local Windows Setup

### 1. Install the required software

Install these applications for Windows:

- PHP 8.3 (64-bit is recommended)
- Composer 2
- Git, if the project will be cloned from a Git repository

Add the PHP directory to the Windows `PATH`. In `php.ini`, enable the extensions
required by the project, especially:

```ini
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
```

Confirm that PHP and Composer are available in PowerShell:

```powershell
php --version
composer --version
php -m
```

### 2. Configure the Laravel project

From the repository root in PowerShell:

```powershell
Set-Location data\mybackend
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create a MySQL database and update the `DB_*` settings in `.env`, then run:

```powershell
php artisan migrate
```

Skip `Copy-Item` when an existing `.env` contains settings that must be retained.

### 3. Start the local server

```powershell
php artisan serve --host=127.0.0.1 --port=9001
```

The API is now available at `http://localhost:9001`.

## API Authentication

Registration and login return a Sanctum token. Send that token on every protected
request:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

Do not place tokens in a URL or commit real tokens to source control.

## API Endpoints

| Method | Endpoint | Authentication | Description |
| --- | --- | --- | --- |
| `POST` | `/api/register` | Public | Register a user and return a token |
| `POST` | `/api/login` | Public | Log in and return a token |
| `GET` | `/api/users` | Bearer token | List all users |
| `GET` | `/api/me` | Bearer token | Return the authenticated user |
| `PUT` | `/api/change-password` | Bearer token | Change the authenticated user's password |
| `POST` | `/api/logout` | Bearer token | Revoke the current token |

### Register

```http
POST /api/register
Content-Type: application/json
Accept: application/json
```

```json
{
  "name": "Juan Dela Cruz",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

A successful registration returns HTTP `201` with the user and token.

### Login

```json
{
  "email": "juan@example.com",
  "password": "password123"
}
```

Invalid credentials return HTTP `401`. Successful login returns a token that can
be used for protected endpoints.

### Change Password

```json
{
  "current_password": "password123",
  "new_password": "new-password123",
  "new_password_confirmation": "new-password123"
}
```

The new password must contain at least eight characters, must match its
confirmation, and must differ from the current password. Validation failures
return HTTP `422`.

### Common Status Codes

| Status | Meaning |
| --- | --- |
| `200` | Request completed successfully |
| `201` | User registered successfully |
| `401` | Missing, invalid, or revoked token; or invalid login |
| `404` | Route not found |
| `422` | Request validation failed |

## Testing with VS Code REST Client

1. Install the **REST Client** extension in Visual Studio Code.
2. Open `restclient/api-test.http` from the repository root.
3. Start the API on port `9001`.
4. Run **Register** first, followed by **Login**.
5. Run the protected requests. Their bearer tokens are read automatically from
   the named login response.
6. Run **Change password** before **Login using the new password**.

The collection also includes requests that verify root `404`, unauthorized
access, and an unknown API route.

## Automated Tests

Using Docker, run this command from the repository root:

```bash
docker compose run --rm -w /data/mybackend ubuntu php artisan test
```

For a local PHP installation, run this command inside `data/mybackend`:

```bash
php artisan test
```

Check the registered routes with:

```bash
php artisan route:list
```

## GitHub CI/CD Deployment

The workflow at `.github/workflows/prod-deploy.yml` runs only when a commit is
pushed to the `prod` branch. Merging a pull request into `prod` creates a push,
so it also starts the workflow. Pushes and merges to `main` do not deploy.

The pipeline performs these steps in order:

1. Install PHP 8.3 and Composer dependencies.
2. Run Laravel Pint and the automated tests.
3. Stop immediately if CI fails.
4. Install optimized production dependencies without development packages.
5. Move the contents of Laravel's `public/` directory to the shared-hosting root
   and install an adjusted `index.php` front controller.
6. Create the production `.env` from a protected GitHub Secret.
7. Upload the application only to `mybackend.free.nf/htdocs/` over FTP.

The deployment excludes tests, Git metadata, GitHub workflow files, local logs,
and development dependencies. The FTP action is restricted to the exact
`mybackend.free.nf/htdocs/` directory and does not use a clean-slate deletion.

### Required GitHub Secrets

In the GitHub repository, open **Settings > Secrets and variables > Actions** and
create these repository or `production` environment secrets:

| Secret | Value |
| --- | --- |
| `FTP_HOST` | FTP server hostname |
| `FTP_USERNAME` | FTP account username |
| `FTP_PASSWORD` | FTP account password |
| `INFINITYFREE_ENV_FILE` | Complete multiline production `.env` contents |

Do not place FTP credentials in the workflow, `.env.example`, or repository.
Because FTP is unencrypted, use FTPS or SFTP instead when the hosting provider
supports it.

### Production Branch Flow

Create and push the production branch once if it does not exist:

```bash
git switch -c prod
git push -u origin prod
```

For later releases, merge reviewed changes into `prod` and push:

```bash
git switch prod
git merge main
git push origin prod
```

Monitor the run from the repository's **Actions** tab. GitHub's `production`
environment can optionally require owner approval before the deploy job starts.

## Configuration and Security

- Keep `APP_DEBUG=false` when responses may be seen by other users. This prevents
  stack traces and internal file paths from appearing in JSON errors.
- Never commit `.env`, application keys, database credentials, or API tokens.
- Change `APP_URL` to the correct URL for each environment.
- Run `php artisan config:clear` after changing `.env` during development.
- Use HTTPS outside local development.
- The built-in `artisan serve` command is for development only. Use a production
  web server and process manager when deploying the API.

## Troubleshooting

### `could not find driver` for MySQL

Enable or install `pdo_mysql` / `php8.3-mysql`, then restart the PHP process.

### `Connection refused` or MySQL connection timeout

Check `DB_HOST`, `DB_PORT`, firewall rules, and whether MySQL accepts connections
from the Docker container or local machine. Inside Docker, do not use `127.0.0.1`
for a MySQL server running directly on the host.

### `No application encryption key has been specified`

```bash
php artisan key:generate
```

### Database table does not exist

```bash
php artisan migrate
```

### Port 9001 is already in use

Stop the other process or change both the Docker port mapping and Laravel server
port. For local PHP, choose another port with `--port=9002`.

### API returns old configuration values

```bash
php artisan optimize:clear
```

### Docker container exits immediately

View the startup error:

```bash
docker compose logs ubuntu
```

## License

This project uses the Laravel framework, which is open-sourced software licensed
under the MIT license.
