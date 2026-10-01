<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'lab';
$password = getenv('RABBITMQ_PASSWORD') ?: 'lab';

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

// Do not give this worker another message while one is still unacknowledged.
$channel->basic_qos(null, 1, null);

$workerId = (string) getmypid();

$callback = static function (AMQPMessage $message) use ($workerId): void {
    $payload = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);

    $name = (string) ($payload['name'] ?? 'unknown');
    $duration = max(0, (int) ($payload['duration'] ?? 0));
    $id = (string) ($payload['id'] ?? 'unknown');

    echo sprintf(
        "[worker:%s] Received %s (%ss, id=%s)\n",
        $workerId,
        $name,
        $duration,
        $id,
    );

    sleep($duration);

    echo sprintf("[worker:%s] Done %s (id=%s)\n", $workerId, $name, $id);

    // Manual ACK: RabbitMQ may now remove this delivery from the queue.
    $message->ack();
};

$channel->basic_consume(
    'task_queue',
    '',
    false,
    false, // auto_ack = false: the worker must ACK manually
    false,
    false,
    $callback,
);

echo sprintf("[worker:%s] Waiting for tasks. Press Ctrl+C to stop.\n", $workerId);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
