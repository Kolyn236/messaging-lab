# Lesson 03 — RabbitMQ Exchanges, Bindings and Routing Keys

## Goal

Route messages selectively through a named direct exchange instead of publishing through the default exchange.

```text
                                      routing_info (bound with "info")
Producer -> lab.direct direct exchange
                                      routing_error (bound with "error")
```

By the end of this lesson you should be able to explain:

- an exchange receives messages from producers and decides where to route them
- a queue stores messages until consumers receive them
- a binding connects a queue to an exchange using routing rules
- a routing key is the label that a direct exchange matches against binding keys

## 1. Start the lab

```bash
make init
```

Lesson 03 uses the durable named direct exchange `lab.direct`. Both the producer and consumer declare it identically, so either can start first. A consumer also declares its chosen durable queue and binds that queue explicitly.

## 2. Start two selectively bound consumers

Terminal A:

```bash
make rabbit-route-consume ROUTING_QUEUE=routing_info ROUTING_KEY=info
```

Terminal B:

```bash
make rabbit-route-consume ROUTING_QUEUE=routing_error ROUTING_KEY=error
```

Inspect the bindings in another terminal:

```bash
make rabbit-routing
```

The important bindings are:

```text
lab.direct -> routing_info  routing key: info
lab.direct -> routing_error routing key: error
```

Queue names and routing keys are separate. The examples make them visibly different to emphasize that the binding, not a naming convention, connects them.

## 3. Publish messages with caller-supplied routing keys

Terminal C:

```bash
make rabbit-route-send ROUTING_KEY=info ROUTED_MSG="Service started"
make rabbit-route-send ROUTING_KEY=error ROUTED_MSG="Database unavailable"
```

Expected result:

- Terminal A receives only `Service started`.
- Terminal B receives only `Database unavailable`.
- The producer publishes to `lab.direct`; it does not publish to a queue or use the default exchange.

This demonstrates selective delivery. The `info` message is not delivered to `routing_error` because that queue has only an `error` binding. Likewise, an `error` message is not delivered to the queue bound only with `info`.

## 4. Try an unbound routing key

```bash
make rabbit-route-send ROUTING_KEY=debug ROUTED_MSG="Diagnostic details"
```

Neither consumer receives this message. A direct exchange routes only when a message routing key exactly matches a binding key. With no `debug` binding, the exchange has no destination for this message.

## 5. The four concepts

### Exchange

The producer publishes a message to an exchange. The exchange applies its type-specific routing rules. The `lab.direct` exchange uses exact routing-key matches.

### Queue

A queue buffers messages for consumers. Exchanges route; queues store. A producer in this lesson does not need to know either queue name.

### Binding

A binding is a routing relationship between an exchange and a queue. Declaring an exchange and queue is not enough: `queue_bind` creates the path the message can follow.

### Routing key

The producer attaches a routing key when publishing. For a direct exchange, RabbitMQ compares it with each binding key and copies the message to every matching bound queue.

## 6. Contrast with earlier lessons

Lessons 01 and 02 publish through the unnamed default exchange, where the routing key is the queue name. Lesson 03 makes the exchange and binding explicit:

```text
Earlier: producer -> default exchange -> queue named by routing key
Now:     producer -> lab.direct -> matching bindings -> selected queues
```

The existing Lesson 01 and Lesson 02 commands still work unchanged.

## Questions before Lesson 04

1. Why does the producer know a routing key but not a queue name?
2. What happens when no binding matches a direct-exchange routing key?
3. Can two queues bind to the same exchange with the same binding key?
4. How is a binding different from a queue?
5. Why must the producer and consumers declare `lab.direct` with compatible settings?
