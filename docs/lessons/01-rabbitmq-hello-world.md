# Lesson 01 — RabbitMQ: Producer → Queue → Consumer

## Goal

Build the smallest useful RabbitMQ flow without Symfony Messenger or other framework abstractions.

```text
Producer ──publish──> RabbitMQ ──deliver──> Consumer
                         │
                         └── queue: hello
```

By the end of this lesson you should be able to explain these words without looking them up:

- broker
- producer
- consumer
- connection
- channel
- queue
- durable queue

## 1. Start the lab

```bash
make init
```

This builds the PHP container, starts RabbitMQ, waits for the broker health check, and installs Composer dependencies.

RabbitMQ exposes two ports:

- `5672` — AMQP traffic used by the PHP application
- `15672` — RabbitMQ Management UI

Open `http://localhost:15672` and sign in with:

```text
user: lab
password: lab
```

## 2. Start the consumer

Terminal A:

```bash
make rabbit-consume
```

The process stays alive because a consumer waits for RabbitMQ to push messages to it.

## 3. Publish a message

Terminal B:

```bash
make rabbit-send MSG="Hello from producer"
```

Expected output in Terminal A:

```text
[consumer] Received: Hello from producer
```

## 4. What actually happened?

The producer does four important things:

1. opens a TCP connection to RabbitMQ;
2. opens an AMQP channel inside that connection;
3. declares the `hello` queue;
4. publishes a message using routing key `hello`.

The consumer:

1. opens its own connection;
2. opens its own channel;
3. declares the same queue;
4. subscribes to it and waits for deliveries.

Both programs declare the queue as durable:

```php
$channel->queue_declare('hello', false, true, false, false);
```

The important arguments for now are:

```text
name       = hello
durable    = true
exclusive  = false
autoDelete = false
```

RabbitMQ 4.3+ no longer permits transient (`durable=false`) non-exclusive classic queues by default. A durable queue is also the sensible choice here because producer and consumer use separate connections and need to refer to the same named queue.

Durability of the queue and persistence of messages are two separate things:

```text
durable queue
    = the queue definition survives a broker restart

persistent message
    = the publisher asks RabbitMQ to retain the message across a broker restart
```

Lesson 01 uses a durable queue but still publishes ordinary, non-persistent messages. We will add message persistence when we study delivery guarantees.

The call below may look as if the producer publishes directly to a queue:

```php
$channel->basic_publish($message, '', 'hello');
```

It does not. The empty exchange name means RabbitMQ's built-in default exchange. The routing key `hello` routes the message to the queue named `hello`.

We will create our own exchanges in a later lesson.

## 5. Experiments — do these before the next lesson

### Experiment A — producer before consumer

Stop the consumer, then run:

```bash
make rabbit-send MSG="one"
make rabbit-send MSG="two"
make rabbit-send MSG="three"
make rabbit-queues
```

Predict the result before running `make rabbit-queues`.

Then start the consumer again:

```bash
make rabbit-consume
```

Watch what happens to the queued messages.

### Experiment B — inspect RabbitMQ

While the consumer is running:

```bash
make rabbit-queues
```

Compare the `messages` and `consumers` columns with what you see in the Management UI.

### Experiment C — declaration from both sides

Both `send.php` and `receive.php` contain:

```php
$channel->queue_declare('hello', false, true, false, false);
```

Think about:

1. why both programs declare the queue instead of assuming the other program already created it;
2. why the queue must be declared with the same properties by both applications;
3. what `durable=true` guarantees, and what it does not guarantee.

## 6. Intentionally missing from lesson 01

We are not solving message delivery reliability yet.

Not covered yet:

- manual acknowledgements (`ack` / `nack`)
- persistent messages
- publisher confirms
- custom exchanges and bindings
- retries
- dead-letter queues
- prefetch

Those concepts become much easier to understand after the basic message path is visible.

## Questions to answer before lesson 02

1. What is the difference between a RabbitMQ connection and a channel?
2. Where is a message stored while no consumer is running?
3. Why does the producer terminate but the consumer stays alive?
4. What does the empty exchange name in `basic_publish()` mean?
5. What happens if three messages are published while the consumer is stopped?
6. What is the difference between a durable queue and a persistent message?
