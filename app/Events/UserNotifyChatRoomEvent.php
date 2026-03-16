<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserNotifyChatRoomEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $school_id;
    public $from;
    public $mobile_no;
    public $subject;
    public $message;
    public $entity_id;
    public $entity_name;
    public $via;
    public $data;
    public $executed_at;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($school_id,$from,$mobile_no,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at)
    {
        //
        $this->school_id   = $school_id;
        $this->from        = $from;
        $this->mobile_no   = $mobile_no;
        $this->subject     = $subject;
        $this->message     = $message;
        $this->entity_id   = $entity_id;
        $this->entity_name = $entity_name;
        $this->via         = $via;
        $this->data        = $data;
        $this->executed_at = $executed_at;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
