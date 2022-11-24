<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;


class SocketController extends Controller implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $this->clients->attach($connection);

        $querystring = $connection->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token']))
        {
            User::where('token', $queryarray['token'])
            ->update(['connection_id' => $connection->resourceId]);
        }
    }

    public function onMessage(ConnectionInterface $connection, $message)
    {

    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->clients->detach($connection);

        $querystring = $connection->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);

        if (isset($queryarray['token']))
        {
            User::where('token', $queryarray['token'])
            ->update(['connection_id' => 0]);
        }
    }

    public function onError(ConnectionInterface $connection, \Exception $exception)
    {
        echo "An error has occurred: {$exception->getMessage()} \n";
        $connection->close();
    }
}
