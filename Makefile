MSG ?= Hello RabbitMQ
TASK ?= demo-task
DURATION ?= 5

.PHONY: init build up down install rabbit-send rabbit-consume rabbit-queues rabbit-task rabbit-worker rabbit-work-queues

init: build up install

build:
	docker compose build

up:
	docker compose up -d

install:
	docker compose exec app composer install

down:
	docker compose down

rabbit-send:
	docker compose exec app php experiments/rabbitmq/01-hello-world/send.php "$(MSG)"

rabbit-consume:
	docker compose exec app php experiments/rabbitmq/01-hello-world/receive.php

rabbit-queues:
	docker compose exec rabbitmq rabbitmqctl list_queues name messages consumers

rabbit-task:
	docker compose exec app php experiments/rabbitmq/02-work-queues/send.php "$(TASK)" "$(DURATION)"

rabbit-worker:
	docker compose exec app php experiments/rabbitmq/02-work-queues/worker.php

rabbit-work-queues:
	docker compose exec rabbitmq rabbitmqctl list_queues name messages_ready messages_unacknowledged consumers
