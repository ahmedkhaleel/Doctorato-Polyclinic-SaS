<?php

namespace App\Events\OnlineConsultation;

use App\Models\OnlineConsultation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OnlineConsultation $consultation,
        public string $role,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('online-consultation.' . $this->consultation->id)];
    }

    public function broadcastAs(): string
    {
        return 'participant.left';
    }

    public function broadcastWith(): array
    {
        return [
            'consultation_id' => $this->consultation->id,
            'role' => $this->role,
            'left_at' => now()->toIso8601String(),
        ];
    }
}
