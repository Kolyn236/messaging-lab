<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

const EXCHANGE_NAME = 'lab.direct';

$routingKey = trim($argv[1] ?? '');
$messageBody = $argv[2] ?? '';

if ($routingKey === '' || $messageBody === '') {
    fwrite(STDERR, "Usage: php send.php <routing-key> <message>\n");
    exit(1);
}

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'lab';
$password = getenv('RABBITMQ_PASSWORD') ?: 'lab';

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

$channel->exchange_declare(EXCHANGE_NAME, 'direct', false, true, false);

$message = new AMQPMessage($messageBody, [
    'content_type' => 'text/plain',
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);
$channel->basic_publish($message, EXCHANGE_NAME, $routingKey);

echo sprintf(
    "[producer] Sent to %s with routing key %s: %s\n",
    EXCHANGE_NAME,
    $routingKey,
    $messageBody,
);

$channel->close();
$connection->close();
