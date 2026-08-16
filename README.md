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

**Lesson 01 — RabbitMQ: Producer → Queue → Consumer**

Read: [`docs/lessons/01-rabbitmq-hello-world.md`](docs/lessons/01-rabbitmq-hello-world.md)

Quick start:

```bash
make init
```

Terminal A:

```bash
make rabbit-consume
```

Terminal B:

```bash
make rabbit-send MSG="Hello RabbitMQ"
```

RabbitMQ Management UI: `http://localhost:15672`

Credentials:

```text
lab / lab
```
