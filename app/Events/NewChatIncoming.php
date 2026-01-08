<?php

namespace App\Events;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatIncoming implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ChatSession $session, ChatMessage $message)
    {
        $this->session = $session;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast ke channel global atau private per user yang di-assign
        $channels = [new PrivateChannel('inbox.global')];

        if ($this->session->assigned_user_id) {
            $channels[] = new PrivateChannel('user.' . $this->session->assigned_user_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new.chat.incoming';
    }
}

