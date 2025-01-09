<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Factory as LoopFactory;

class SocketIoBroadcastServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('socketio', function ($app, array $config) {
            return new class implements Broadcaster {
                public function broadcast(array $channels, $event, array $payload = [])
                {
                    $loop = LoopFactory::create();
                    $connector = new Connector($loop);

                    $connector('wss://smart-turkey-crisp.ngrok-free.app', ['Origin' => 'https://localhost'])
                        ->then(function (WebSocket $conn) use ($channels, $event, $payload, $loop) {
                            foreach ($channels as $channel) {
                                $conn->send(json_encode([
                                    'channel' => $channel,
                                    'event' => $event,
                                    'data' => $payload,
                                ]));
                            }
                            $conn->close();
                            $loop->stop();
                        }, function ($e) use ($loop) {
                            echo "Could not connect: {$e->getMessage()}\n";
                            $loop->stop();
                        });

                    $loop->run();
                }
            };
        });
    }
}