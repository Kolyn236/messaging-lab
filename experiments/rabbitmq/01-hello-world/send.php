<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'lab';
$password = getenv('RABBITMQ_PASSWORD') ?: 'lab';
$messageBody = $argv[1] ?? 'Hello RabbitMQ';

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

// RabbitMQ 4.3+ denies transient non-exclusive queues by default.
// The third argument is `durable`, so `true` makes the queue definition
// survive a broker restart. Message persistence is a separate concern.
$channel->queue_declare('hello', false, true, false, false);

$message = new AMQPMessage($messageBody);
$channel->basic_publish($message, '', 'hello');

echo sprintf("[producer] Sent: %s\n", $messageBody);

$channel->close();
$connection->close();
