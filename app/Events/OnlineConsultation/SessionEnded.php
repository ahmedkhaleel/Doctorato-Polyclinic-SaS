<?php

namespace App\Events\OnlineConsultation;

use App\Models\OnlineConsultation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OnlineConsultation $consultation,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('online-consultation.' . $this->consultation->id)];
    }

    public function broadcastAs(): string
    {
        return 'session.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'consultation_id' => $this->consultation->id,
            'ended_at' => optional($this->consultation->session_ended_at)->toIso8601String(),
            'duration_seconds' => $this->consultation->duration_seconds,
        ];
    }
}
