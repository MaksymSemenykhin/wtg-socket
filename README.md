# WTG Socket — Real-time messaging

A Laravel application with user registration, authentication, and real-time messaging via WebSockets (Laravel Reverb). Only authenticated users can access the dashboard and send messages. Messages are stored in the database and broadcast to the recipient over a private channel; offline users see missed messages on next login.

## Requirements (project spec)

- **PHP 8.3+**
- **MySQL 8+**
- **Redis** (for broadcasting and queue)
- **Laravel Reverb** (WebSockets)
- **Docker & Docker Compose** — development and tests run in **WSL via [Laravel Sail](https://laravel.com/docs/sail)**

## Localization

The application is localized for **English (EN)** only. All UI strings, validation messages, and API messages are in English. Locale is set via `APP_LOCALE=en` and `APP_FALLBACK_LOCALE=en` in `.env`. Translation files: `lang/en.json`, `lang/en/messages.php`.

## Stack

- **Laravel** 12
- **Laravel Breeze** (Blade) — registration and login
- **Laravel Reverb** — WebSocket server (per spec)
- **Redis** — broadcasting driver and queue (per spec)
- **MySQL 8** — database (per spec)
- **Alpine.js** + **Laravel Echo** — real-time UI

## Sail configuration (per project spec)

Sail is set up to match the assignment:

| Requirement | Sail setup |
|-------------|------------|
| PHP 8.3+    | `laravel.test` container (PHP 8.5 runtime) |
| MySQL 8+    | `mysql` service (MySQL 8.4) |
| Redis       | `redis` service (broadcasting + queue) |
| Laravel Reverb | Laravel package; run `sail artisan reverb:start` in a separate terminal |

Environment (see `.env.example`): `BROADCAST_CONNECTION=reverb`, `QUEUE_CONNECTION=redis`, `DB_HOST=mysql`, `REDIS_HOST=redis`, plus `REVERB_*` and `VITE_REVERB_*` for the frontend. All commands (migrate, test, queue, reverb) are run **in WSL** via `./vendor/bin/sail ...`.

## Running with WSL and Sail

1. **Clone the repository** and enter the project directory.

2. **Start the environment** (from WSL):

   ```bash
   ./vendor/bin/sail up -d
   ```

3. **Install dependencies** (if not already):

   ```bash
   ./vendor/bin/sail composer install
   ./vendor/bin/sail npm install
   ```

4. **Configure environment**:

   - Copy `.env.example` to `.env`. It is already set for Sail (MySQL, Redis, Reverb). Run `./vendor/bin/sail artisan reverb:install` if you need to generate `REVERB_APP_*` and then run `key:generate` if `APP_KEY` is empty.

5. **Run migrations**:

   ```bash
   ./vendor/bin/sail artisan migrate
   ```

6. **Build frontend assets**:

   ```bash
   ./vendor/bin/sail npm run build
   ```

7. **Start the queue worker** (in a separate terminal):

   ```bash
   ./vendor/bin/sail artisan queue:work
   ```

8. **Start Reverb** (in another terminal; leave it running for real-time chat):

   ```bash
   ./vendor/bin/sail artisan reverb:start
   ```

   If you see **"WebSocket connection to ws://localhost:8080 failed"** in the browser console, Reverb is not running or not reachable: (1) Start it with the command above and keep that terminal open; (2) Restart Sail so port 8080 is exposed: `sail down && sail up -d`.

9. The app is served by Sail (see `compose.yaml`; default port is 80, or `APP_PORT` / `VITE_PORT` for Vite).

For local development with hot reload:

```bash
./vendor/bin/sail npm run dev
```

## Code quality and tests

Run these commands **in WSL** via Sail (the app uses MySQL and Redis, so tests need the Sail environment).

- **Laravel Pint** (code style):

  ```bash
  ./vendor/bin/sail exec laravel.test ./vendor/bin/pint
  ./vendor/bin/sail exec laravel.test ./vendor/bin/pint --test   # CI check
  ```

- **PHPStan (Larastan)** (static analysis):

  ```bash
  ./vendor/bin/sail exec laravel.test ./vendor/bin/phpstan analyse
  ```

- **Tests** (PHPUnit) — run in WSL with Sail so MySQL (`DB_DATABASE=testing`) is available:

  ```bash
  ./vendor/bin/sail artisan test
  ```

  Or: `./vendor/bin/sail test` (same as above).

## CI/CD

GitHub Actions runs on push and pull requests to `main` / `master`:

- Install PHP 8.3, MySQL, Redis
- `composer install`, prepare `.env`, run migrations
- **Pint** (`pint --test`)
- **PHPStan**
- **PHPUnit** (`php artisan test`)

See [.github/workflows/ci.yml](.github/workflows/ci.yml).

## Commit convention

This project uses [Conventional Commits](https://www.conventionalcommits.org/):

- `type(scope): short description`
- Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`
- Scope examples: `auth`, `messages`, `broadcasting`, `ci`

Example: `feat(messages): add message store and broadcast event`

## License

MIT.
