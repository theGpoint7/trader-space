<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "socketio", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'socketio'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over WebSockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    // filepath: /home/wsl_user/trader-space/config/broadcasting.php
'connections' => [
    'socketio' => [
        'driver' => 'custom',
        'via' => App\Broadcasting\SocketIoBroadcaster::class,
        'host' => env('SOCKETIO_HOST'),
        'port' => env('SOCKETIO_PORT'),
        'scheme' => 'http',
    ],
],

];