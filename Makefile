MSG ?= Hello RabbitMQ
TASK ?= demo-task
DURATION ?= 5
ROUTING_KEY ?= info
ROUTED_MSG ?= Routed message
ROUTING_QUEUE ?= routing_info

.PHONY: init build up down install rabbit-send rabbit-consume rabbit-queues rabbit-task rabbit-worker rabbit-work-queues rabbit-route-send rabbit-route-consume rabbit-routing

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

rabbit-route-send:
	docker compose exec app php experiments/rabbitmq/03-routing/send.php "$(ROUTING_KEY)" "$(ROUTED_MSG)"

rabbit-route-consume:
	docker compose exec app php experiments/rabbitmq/03-routing/consume.php "$(ROUTING_QUEUE)" "$(ROUTING_KEY)"

rabbit-routing:
	docker compose exec rabbitmq rabbitmqctl list_bindings source_name source_kind destination_name destination_kind routing_key
