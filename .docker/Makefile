.PHONY: build up down

cache-build:
	docker compose --env-file ../.env -f docker-compose.yml build

build:
	docker compose --env-file ../.env -f docker-compose.yml build --no-cache

up:
	docker compose --env-file ../.env -f docker-compose.yml up -d

logup:
	docker compose --env-file ../.env -f docker-compose.yml up

down:
	docker compose -f docker-compose.yml down
