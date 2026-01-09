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

        if($this->message->topic == 4){ // Topic 4 are Shift swap messages

            // Random reply with: 1) Approved or 2) Denied
            $status = collect([1, 2])->random(); 
            $responseText = ($status === 1) 
                ? 'Ja hoor, geen probleem!' 
                : 'Sorry, komt helaas niet uit.';

            // Making sure the shifts gets swapped with the receiver
            if($status == 1){
                $workday = \App\Models\Workday::findOrFail($this->message->workday_id);
                $workday?->update([
                    'worker_id' => $this->message->receiver_id,
                ]);
            }

            // Finalize message state and reset receiver_id
            $this->message->update([
                'status' => $status,
                'response' => $responseText,
                'receiver_id' => null,
            ]);

        }else{

            // Random reply with: 1) Approved or 2) Denied
            $status = collect([1, 2])->random(); 
            $responseText = ($status === 1) 
                ? 'Oke, prima!' 
                : 'Nee, kan helaas niet.';

            $this->message->update([
                'status' => $status,
                'replier' => 'Jeroen Blankevelt',
                'response' => $responseText,
            ]);

        }
        
    }
}
