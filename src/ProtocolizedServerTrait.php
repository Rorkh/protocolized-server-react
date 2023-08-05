<?php

namespace Ren\ProtocolizedServer;

use React\Socket\ConnectionInterface;
use Ren\ProtocolizedServer\ProtocolizedServerTraitInterface;

trait ProtocolizedServerTrait
{
    use ProtocolizedServerTraitInterface;

    public function start(string|int $address): void
    {
        $this->server = new \React\Socket\TcpServer($address);
        $this->handlers = $this->registerHandlers();

        $this->server->on('connection', function (ConnectionInterface $connection) {
            $this->onConnection($connection);
            $client = new ProtocolizedServerClient($connection);

            $connection->on('data', function ($data) use ($connection, $client) {
                [$packetId, $payload] = $this->onDataRead($data);

                if (isset($this->handlers[$packetId])) {
                    $connection->write($this->handlers[$packetId]($client, $payload));
                }
            });
        });

        $this->server->on('error', function (\Exception $e) {
            $this->onError($e);
        });
    }

    public function close(): void
    {
        $this->server->close();
    }
}
