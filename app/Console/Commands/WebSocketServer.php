<?php

namespace App\Console\Commands;

use App\Http\Controllers\SocketController;
use Illuminate\Console\Command;
use Ratchet\server\IoServer;
use Ratchet\server\Httpserver;
use Ratchet\webSocket\WsServer;
use Ratchet\EventLoop\Factory;
use Ratchet\Http\HttpServer as HttpHttpServer;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //return Command::SUCCESS;

        $server = IoServer::factory(
            new HttpHttpServer(
                new WsServer(
                    new SocketController()
                )
            ),8090
        );

        $server->run();
    }
}
