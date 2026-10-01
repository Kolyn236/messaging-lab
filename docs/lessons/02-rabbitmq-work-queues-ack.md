# Lesson 02 — RabbitMQ Work Queues, Manual ACK and Prefetch

## Goal

Move from a simple producer/consumer example to a real work queue with competing workers and reliable delivery.

```text
Producer -> task_queue -> Worker #1
                    \-> Worker #2
```

By the end of this lesson you should be able to explain:

- competing consumers
- manual acknowledgements
- unacknowledged messages
- redelivery after worker failure
- prefetch
- why durable queues and persistent messages are separate concepts

## 1. Switch to the lesson branch

```bash
git fetch origin
git switch agent/lesson-02-work-queues-ack
```

If the branch already exists locally:

```bash
git pull --ff-only origin agent/lesson-02-work-queues-ack
```

## 2. Start two workers

Terminal A:

```bash
make rabbit-worker
```

Terminal B:

```bash
make rabbit-worker
```

Check RabbitMQ:

```bash
make rabbit-work-queues
```

Expected when no jobs are waiting:

```text
name        messages_ready  messages_unacknowledged  consumers
task_queue  0               0                        2
```

## 3. Send jobs

Terminal C:

```bash
make rabbit-task TASK="job-1" DURATION=3
make rabbit-task TASK="job-2" DURATION=3
make rabbit-task TASK="job-3" DURATION=3
make rabbit-task TASK="job-4" DURATION=3
```

Watch how the jobs are distributed between Worker A and Worker B.

The workers are competing consumers: a single message from this queue is delivered to one worker, not both.

## 4. Manual ACK

Lesson 01 used auto acknowledgement:

```php
$channel->basic_consume('hello', '', false, true, false, false, $callback);
```

That tells RabbitMQ to consider a delivery acknowledged automatically.

Lesson 02 uses:

```php
$channel->basic_consume(
    'task_queue',
    '',
    false,
    false,
    false,
    false,
    $callback,
);
```

The important value is:

```text
auto_ack = false
```

After successful processing the worker explicitly runs:

```php
$message->ack();
```

Until that ACK arrives, RabbitMQ keeps the delivery in the unacknowledged state.

## 5. Observe an unacknowledged message

Start one or two workers and send a slow job:

```bash
make rabbit-task TASK="slow-job" DURATION=30
```

Immediately run:

```bash
make rabbit-work-queues
```

While a worker is sleeping, expect approximately:

```text
name        messages_ready  messages_unacknowledged  consumers
task_queue  0               1                        2
```

The message is no longer ready for a new consumer, but RabbitMQ has not forgotten it. It is waiting for an ACK from the current worker.

After the worker finishes and sends ACK:

```text
messages_unacknowledged = 0
```

## 6. Crash a worker during processing

This is the main experiment of the lesson.

Run two workers.

Send a long task:

```bash
make rabbit-task TASK="crash-me" DURATION=30
```

One worker should print that it received the task.

Before the 30 seconds finish, press Ctrl+C in that worker's terminal.

Do not stop the second worker.

Expected behavior:

1. Worker #1 received the message.
2. RabbitMQ marked it unacknowledged.
3. Worker #1 disappeared before sending ACK.
4. RabbitMQ returned/redelivered the message.
5. Worker #2 received the same task ID.
6. Worker #2 completed it and sent ACK.

This is the core reason manual acknowledgements matter for at-least-once processing.

## 7. Prefetch = 1

The worker contains:

```php
$channel->basic_qos(null, 1, null);
```

The important value is `prefetch_count = 1`.

It means RabbitMQ should not give this consumer another message while it already has one unacknowledged delivery.

Run two workers and send three slow jobs quickly:

```bash
make rabbit-task TASK="slow-1" DURATION=30
make rabbit-task TASK="slow-2" DURATION=30
make rabbit-task TASK="slow-3" DURATION=30
```

Immediately inspect the queue:

```bash
make rabbit-work-queues
```

While both workers are busy, you should see approximately:

```text
messages_ready = 1
messages_unacknowledged = 2
consumers = 2
```

Why?

- Worker #1 has one unacknowledged task.
- Worker #2 has one unacknowledged task.
- Prefetch prevents RabbitMQ from pushing `slow-3` to an already busy worker.
- `slow-3` remains ready in the queue.

## 8. Durable queue vs persistent message

The queue is declared with:

```php
$channel->queue_declare('task_queue', false, true, false, false);
```

So the queue is durable.

The producer also creates the message with:

```php
'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
```

These are two different settings:

```text
durable queue      -> queue definition is intended to survive broker restart
persistent message -> message is marked for durable storage
```

Do not treat them as the same concept.

Persistence is about broker restarts. ACK is about whether a consumer successfully completed processing. They solve different failure modes.

## 9. Delivery guarantee we have now

Our current worker is moving toward an at-least-once model:

```text
message may be delivered again
but should not be silently lost when a worker dies before ACK
```

This creates a new problem: duplicate processing.

A worker can perform a side effect successfully and die just before sending ACK:

```text
charge card -> success
worker crashes
ACK never reaches RabbitMQ
RabbitMQ redelivers
charge card runs again
```

That is why idempotency becomes important. We will return to this later.

## Questions before Lesson 03

1. What is the difference between `messages_ready` and `messages_unacknowledged`?
2. Why is ACK sent after the work, not before it?
3. What happens to an unacknowledged message when its connection disappears?
4. What does `prefetch_count = 1` change when workers have different processing times?
5. Why does manual ACK imply that duplicate processing is possible?
6. What failure does message persistence help with that ACK does not?
