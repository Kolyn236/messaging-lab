<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'lab';
$password = getenv('RABBITMQ_PASSWORD') ?: 'lab';

$name = $argv[1] ?? 'demo-task';
$duration = max(0, (int) ($argv[2] ?? 5));

$payload = [
    'id' => bin2hex(random_bytes(4)),
    'name' => $name,
    'duration' => $duration,
];

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

$message = new AMQPMessage(
    json_encode($payload, JSON_THROW_ON_ERROR),
    [
        'content_type' => 'application/json',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ],
);

$channel->basic_publish($message, '', 'task_queue');

echo sprintf(
    "[producer] Sent %s (%ss, id=%s)\n",
    $payload['name'],
    $payload['duration'],
    $payload['id'],
);

$channel->close();
$connection->close();
