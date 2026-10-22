# Order Payment API

A Laravel 12 modular API for user authentication, orders, and simulated payments. It uses JWT authentication, Form Requests, unified JSON responses, cursor pagination, and a gateway strategy that keeps payment providers separate from payment methods.

## Features

- JWT registration, login, refresh, logout, and current-user endpoints.
- Orders with calculated totals, status transitions, ownership policies, and cursor pagination.
- Simulated Kashier, Paymob, and MyFatoorah gateway strategies.
- Configurable gateway/method matrix, with no controller, service, or schema change required when adding a gateway.
- Scramble OpenAPI documentation and a ready-to-import Postman collection.

## Requirements

- PHP 8.4+, Composer, Node.js, and npm.
- PHP extensions: `bcmath`, `pdo_sqlite`/`sqlite3`, `mbstring`, `xml`/`dom`, `curl`, `openssl`, and `fileinfo`.
- Redis plus the PHP Redis extension when using the default local cache configuration.

`bcmath` is required because order prices and totals are decimal money values. Its string arithmetic prevents floating-point rounding errors when calculating item subtotals and order totals.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
npm install
npm run build
composer dev
```

Use `php artisan serve` and `npm run dev` instead of `composer dev` when you only need the HTTP server and Vite separately.

## Environment configuration

Set these values in `.env` before running the API:

| Setting | Purpose |
| --- | --- |
| `APP_KEY`, `APP_URL`, `APP_ENV` | Laravel encryption and application URL/environment. |
| `DB_CONNECTION` plus `DB_DATABASE` (and host/user/password for MySQL) | Application database. SQLite may use `database/database.sqlite`. |
| `JWT_SECRET` | Required to sign and verify bearer tokens; generate it with `php artisan jwt:secret`. |
| `CACHE_STORE=redis`, `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT` | Required by the default payment lock configuration. |
| `SESSION_DRIVER` and `QUEUE_CONNECTION` | The example uses database-backed sessions and queues, so run migrations. |

Tests use an in-memory SQLite database and the array cache store through `phpunit.xml`. Ensure both `pdo_sqlite` and `sqlite3` are enabled in the PHP CLI used by Composer/Pest. Redis is not needed for tests. For coverage, install and enable Xdebug or PCOV.

## Modules

| Module | Responsibility |
| --- | --- |
| `Modules/Core` | Base repository, enums, references, unified API controller, and base request. |
| `Modules/Auth` | User repository, JWT service, authentication requests, resources, and routes. |
| `Modules/Order` | Orders, items, status rules, policies, repository, and service. |
| `Modules/Payment` | Payments, simulated gateways, support matrix, policies, repository, and service. |

## Response format

Successful responses contain a message and optional `data`; validation and business errors contain `message` and `error`. Cursor listings also return `meta` with `next_cursor`, `previous_cursor`, page URLs, and item counts.

```json
{"message":"Order created successfully.","data":{"id":1}}
```

```json
{"message":"The given data was invalid.","error":{"items":["An order must contain at least one item."]}}
```

## Creating data for manual testing

Do not insert orders or order items directly when testing the API: the order service calculates totals and maintains item records.

1. `POST /api/auth/register` or `POST /api/auth/login`, then send `Authorization: Bearer <token>` on protected requests. `php artisan migrate --seed` creates `test@example.com` with password `password`.
2. `POST /api/orders` with at least one item. Each item needs `product_name`, `quantity`, and a positive `price` with at most two decimal places.
3. Read the returned order ID, then confirm it with `PATCH /api/orders/{order}/status` and `{ "status": "confirmed" }`.
4. Process it through `POST /api/orders/{order}/payments`, for example `{ "gateway": "paymob", "method": "apple_pay", "payload": { "simulate_outcome": "success" } }`.

## Adding a payment gateway

1. Add a case to `Modules/Payment/app/Enums/PaymentGateway.php`.
2. Add a class under `Modules/Payment/app/Gateways` that extends `SimulatedGateway` and returns that enum case.
3. Register the driver and supported methods in `Modules/Payment/config/payment.php`.

For example, `HyperPay = 'hyperpay'` plus `HyperPayGateway extends SimulatedGateway` and a `hyperpay` configuration entry makes the provider available without changing requests, controllers, services, resources, or migrations.

## Documentation and quality checks

- Interactive API docs: `http://localhost:8000/docs/api`
- OpenAPI export: [docs/api.json](docs/api.json)
- Postman collection: [docs/postman_collection.json](docs/postman_collection.json)
- Postman environment: [docs/postman_environment.json](docs/postman_environment.json)

```bash
composer test
./vendor/bin/pest --parallel
./vendor/bin/pint --test
./vendor/bin/pest --coverage --min=80
```

## Notes and assumptions

- Gateways are simulations only. No real provider credentials, SDK calls, webhooks, redirects, or settlement reconciliation are included because full payment integration is outside this task’s scope. The purpose is provider extensibility and deterministic API behavior.
- The API currently has one version and uses the `/api` prefix. API versioning is intentionally not implemented; introduce a `/api/v1` route group before a breaking public API change.
- Orders start as `pending`; only pending orders can be edited. A confirmed order may be cancelled but cannot return to pending.
- Only confirmed orders can be paid. Failed payments may be retried; a successful payment prevents another successful charge for the same order.
- A declined payment returns HTTP `402` and is stored as `failed`. Successful deletion returns HTTP `200`, not `204`, to retain the JSON response envelope.
- API routes are rate-limited to 10 requests per minute; authentication routes to 5 requests per minute.
- Telescope and Scramble’s documentation UI are local-development tools by default. Configure their authorization before exposing them outside a trusted environment.
