# Payment Ecosystem Task

A monorepo microservices platform for processing payments, detecting fraud, and delivering email notifications. Services communicate exclusively through RabbitMQ — no synchronous HTTP between them.

## Services

- **payment-gateway** — Symfony 7.4 / MySQL. REST API, transaction persistence, message orchestration.
- **fraud-engine** — Go 1.25 / Fiber v3. Fraud/risk checks, publishes outcomes.
- **notification-manager** — Symfony 7.4 / CLI. Email delivery via SMTP.

Supporting infrastructure: RabbitMQ (broker), MySQL (gateway only), Mailpit (local SMTP + inbox), Nginx (reverse proxy).

## Running

Copy the environment file — defaults work out of the box:

```bash
cp .env.example .env
```

Start everything:

```bash
make build            # build all Docker images from scratch
make install          # composer install for all PHP services (no running containers needed)
make up               # start all containers in detached mode
make migrate          # run Doctrine migrations (payment-gateway)
```

`make install` installs PHP vendor dependencies for both Symfony services via `docker compose run --no-deps`, so no RabbitMQ or MySQL is required at this stage.

- API: `http://localhost:8080`
- RabbitMQ UI: `http://localhost:15672` (guest / guest)
- Mailpit inbox: `http://localhost:8025`

## API

**Create a payment**

```
POST /api/v1/payments
Request-Id: <idempotency-key>
Content-Type: application/json

{
  "user_id": "018f4e1a-0000-7000-8000-000000000001",
  "user_email": "user@example.com",
  "amount": 149.99,
  "currency": "EUR",
  "payment_method": "card"
}
```

**Manually confirm a payment**

```
POST /api/v1/payments/{transactionId}/confirm
```

## Tests

```bash
make test                # all services
make test-gateway        # payment-gateway
make test-notification   # notification-manager
make test-fraud          # fraud-engine
```

## Architectural Decisions

**Async-only communication** — services talk only via RabbitMQ, keeping boundaries clean and allowing independent scaling.

**Custom JSON serializers** — each transport has a dedicated serializer that produces plain JSON, making the wire format interoperable with non-PHP consumers. Publish-only serializers throw on `decode()` and consume-only on `encode()`.

**Strategy pattern for notification recipients** — `RecipientManager` resolves the right recipient (customer or admin) based on the fraud outcome, keeping dispatch logic in `NotificationService` unchanged when adding new recipient types.

**Idempotency via Request-Id** — the header maps to a unique `request_id` column; duplicate submissions return `409 Conflict` at the database level.

**UUID v7** — time-ordered UUIDs keep MySQL index fragmentation low while remaining globally unique.

**Exponential backoff retries** — all consumers retry up to 5 times (1 s → 2 s → 4 s → 8 s → 16 s, capped at 32 s). Symfony services use Messenger's built-in `retry_strategy`; the Go consumer implements an in-process retry loop and `Nack`s with `requeue=false` after exhausting retries.
