<?php
// app/Events/BtcPriceUpdated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BtcPriceUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $price;

    public function __construct($price)
    {
        $this->price = $price;
    }

    public function broadcastOn()
    {
        return new Channel('btc-price');
    }
}