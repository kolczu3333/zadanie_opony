.PHONY: up down secrets install migrate import test

up:
	@test -f .env.dev.local || cp .env.dev.local.example .env.dev.local
	docker compose up -d

secrets:
	docker compose exec app bin/console secrets:decrypt-to-local --force --env=dev

down:
	docker compose down

install:
	docker compose exec app composer install

migrate:
	docker compose exec app bin/console doctrine:migrations:migrate -n

import:
	docker compose exec app bin/console app:import-stock /app/data/lorotom.csv lorotom
	docker compose exec app bin/console app:import-stock /app/data/trah.csv trah

test-db:
	docker compose exec database sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS stock_test; GRANT ALL ON stock_test.* TO '\''stock'\''@'\''%'\''; FLUSH PRIVILEGES;"'

test: test-db
	docker compose exec app bin/phpunit
