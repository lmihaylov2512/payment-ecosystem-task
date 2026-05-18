install: install-gateway install-notification

install-gateway:
	docker compose run --rm --no-deps payment-gateway composer install

install-notification:
	docker compose run --rm --no-deps notification-manager composer install

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose build --no-cache

logs:
	docker compose logs -f

logs-gateway:
	docker compose logs -f payment-gateway

logs-worker:
	docker compose logs -f payment-gateway-consumer

logs-notification:
	docker compose logs -f notification-manager

logs-fraud:
	docker compose logs -f fraud-engine

test: test-gateway test-notification test-fraud

test-gateway:
	docker compose exec payment-gateway php bin/phpunit

test-notification:
	docker compose exec notification-manager php bin/phpunit

test-fraud:
	docker compose exec fraud-engine go test ./...

migrate:
	docker compose exec payment-gateway php bin/console doctrine:migrations:migrate --no-interaction

shell-gateway:
	docker compose exec payment-gateway sh

shell-notification:
	docker compose exec notification-manager sh

shell-fraud:
	docker compose exec fraud-engine sh
