# messaging-lab

Hands-on learning repository for RabbitMQ and Apache Kafka.

The course starts with low-level messaging primitives before framework abstractions such as Symfony Messenger are introduced.

## Course route

```text
RabbitMQ basics
    ↓
Work queues + acknowledgements
    ↓
Exchanges + routing
    ↓
Retries + DLQ + publisher confirms
    ↓
Symfony Messenger + RabbitMQ
    ↓
Kafka producer / consumer
    ↓
Partitions + consumer groups + offsets
    ↓
Replay + delivery semantics
    ↓
RabbitMQ + Kafka in one application
    ↓
Outbox + idempotency + production patterns
```

## Current lesson

**Lesson 03 — RabbitMQ: Exchanges, Bindings and Routing Keys**

Read: [`docs/lessons/03-rabbitmq-exchanges-routing.md`](docs/lessons/03-rabbitmq-exchanges-routing.md)

Quick start:

```bash
make init
```

Terminal A (messages routed as `info`):

```bash
make rabbit-route-consume ROUTING_QUEUE=routing_info ROUTING_KEY=info
```

Terminal B (messages routed as `error`):

```bash
make rabbit-route-consume ROUTING_QUEUE=routing_error ROUTING_KEY=error
```

Terminal C:

```bash
make rabbit-route-send ROUTING_KEY=info ROUTED_MSG="Service started"
make rabbit-route-send ROUTING_KEY=error ROUTED_MSG="Service failed"
```

RabbitMQ Management UI: `http://localhost:15672`

Credentials:

```text
lab / lab
```
