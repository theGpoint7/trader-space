<?php
// filepath: /home/wsl_user/trader-space/app/Broadcasting/SocketIoBroadcaster.php
namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Support\Arr;
use GuzzleHttp\Client;

class SocketIoBroadcaster extends Broadcaster implements BroadcasterContract
{
    protected $client;
    protected $host;
    protected $port;
    protected $scheme;

    public function __construct()
    {
        $this->client = new Client();
        $this->host = config('broadcasting.connections.socketio.host');
        $this->port = config('broadcasting.connections.socketio.port');
        $this->scheme = config('broadcasting.connections.socketio.scheme');
    }

    public function auth($request)
    {
        return true;
    }

    public function validAuthenticationResponse($request, $result)
    {
        return true;
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        $data = [
            'channels' => $this->formatChannels($channels),
            'event' => $event,
            'data' => $payload,
        ];

        $this->client->post("{$this->scheme}://{$this->host}:{$this->port}/broadcast", [
            'json' => $data,
        ]);
    }

    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            return (string) $channel;
        }, $channels);
    }
}