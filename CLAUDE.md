# Project Instructions

This repository is a monorepo for a microservices payment ecosystem.

## Architecture

- `services/payment-gateway` is Symfony 7 and owns the MySQL transactions database.
- `services/fraud-engine` is Go with Fiber v3 and owns fraud/risk checks.
- `services/notification-manager` is Symfony 7 and owns email notifications.
- Services communicate asynchronously through RabbitMQ only.
- Do not introduce synchronous HTTP communication between services unless explicitly requested.

## RabbitMQ

Use one topic exchange:

- Exchange: `payment.events`
- Routing keys:
    - `payment.initiated`
    - `payment.processed`
    - `payment.notification`

Queues:

- `fraud-engine.payment-initiated`
- `payment-gateway.payment-processed`
- `notification-manager.payment-notification`

Messages must use the documented JSON envelope, not Symfony’s default serialized class payload.

## Database

Only Payment Gateway uses MySQL.

The `transactions` table stores:

- `id` - unsinged integer, internal ID
- `transaction_id` - UUID v7, unique transaction identifier
- `user_id` - UUID, the corresponding user identifier
- `user_email` - string, the customer e-mail address
- `amount` - decimal, transaction amount
- `currency` - string, transaction currency
- `payment_method` - string, transaction payment method, e.g. card, bank_transfer, cash, etc.
- `status` - enum(pending, accepted, rejected, flagged)
- `request_id` - string, unique request identifier, idempotency creation
- `correlation_id` - UUID v7, messaging correlation identifier
- `created_at` - current timestamp
- `updated_at` - nullable timestamp, when transaction is updated
- `accepted_at` - nullable timestamp, when transaction is approved

Do not add a shared database between services.

## Development Rules

- Keep services independently runnable.
- Keep service boundaries clean.
- Add or update tests for behavior changes.
- Do not modify unrelated services unless needed for an integration contract.
/.
## Git Safety - Strict Rules

Claude Code must not create or modify Git history unless the user explicitly asks for the exact Git action.

The user owns all commits, branches, merges, pushes, and history changes.

### Allowed Git Commands

Claude Code may run read-only Git inspection commands:

```text
git status
git diff
git log
git branch
git show
