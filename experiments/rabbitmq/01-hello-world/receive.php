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

$channel->queue_declare('hello', false, false, false, false);

$callback = static function (AMQPMessage $message): void {
    echo sprintf("[consumer] Received: %s\n", $message->getBody());
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

echo "[consumer] Waiting for messages. Press Ctrl+C to stop.\n";

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
