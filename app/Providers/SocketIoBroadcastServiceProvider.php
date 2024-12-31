<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;

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
                    $socket = new \Ratchet\Client\WebSocket('ws://localhost:4000', ['Origin' => 'http://localhost']);
                    $socket->on('open', function ($conn) use ($channels, $event, $payload) {
                        foreach ($channels as $channel) {
                            $conn->send(json_encode([
                                'channel' => $channel,
                                'event' => $event,
                                'data' => $payload,
                            ]));
                        }
                        $conn->close();
                    });
                }
            };
        });
    }
}
