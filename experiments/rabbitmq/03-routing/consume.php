<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

const EXCHANGE_NAME = 'lab.direct';

$queueName = trim($argv[1] ?? '');
$routingKey = trim($argv[2] ?? '');

if ($queueName === '' || $routingKey === '') {
    fwrite(STDERR, "Usage: php consume.php <queue> <routing-key>\n");
    exit(1);
}

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'lab';
$password = getenv('RABBITMQ_PASSWORD') ?: 'lab';

$connection = new AMQPStreamConnection($host, $port, $user, $password);
$channel = $connection->channel();

$channel->exchange_declare(EXCHANGE_NAME, 'direct', false, true, false);
$channel->queue_declare($queueName, false, true, false, false);
$channel->queue_bind($queueName, EXCHANGE_NAME, $routingKey);

$callback = static function (AMQPMessage $message) use ($queueName, $routingKey): void {
    echo sprintf(
        "[consumer:%s] Received %s message: %s\n",
        $queueName,
        $routingKey,
        $message->getBody(),
    );
};

$channel->basic_consume($queueName, '', false, true, false, false, $callback);

echo sprintf(
    "[consumer:%s] Bound to %s with routing key %s. Press Ctrl+C to stop.\n",
    $queueName,
    EXCHANGE_NAME,
    $routingKey,
);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
