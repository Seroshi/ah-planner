<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReplyToMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;

    /**
     * Create a new job instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Random reply with: 1) Approved or 2) Denied
        $status = collect([1, 2])->random(); 
        $responseText = ($status === 1) 
            ? 'Oke, prima!' 
            : 'Nee, kan helaas niet.';

        $this->message->update([
            'status' => $status,
            'response' => $responseText,
        ]);
    }
}
