<table wire:poll.10s class="table-fixed w-full max-w-3xl">

    <tr class="text-lg text-left">
        <th class="font-bold w-[60px] text-left">Status</th>
        <th class="font-bold pl-3 w-[60%]">Onderwerp</th>
        <th class="font-bold pl-3">Verstuurd</th>
    </tr>

    <!-- Message item -->
    @foreach($this->messages() as $message)
        <tr class=" hover-gray cursor-pointer message mb-1 first:rounded-t-lg 
            {{$message->read ? 'font-light bg-[#f0f0f0]' : 'font-medium bg-[#e6e6e6]'}}
            {{ $loop->first ? 'rounded-tl-xl' : '' }} {{ $loop->last ? 'rounded-bl-xl' : '' }}"
            @click="$dispatch('set-modal-messsage-data', 
            { 
                messageId: '{{ $message->id }}',
                topic: '{{ $this->options[$message->topic]['label'] }}',
            }),
            showMessageModal = true"
        >
            <!-- Status icon -->
            <td class="p-3">
                <div class="flex items-center justify-center">
                    @if($message->status == 1)
                        <div class="rounded-full w-[25px] h-[25px] shrink-0 text-indigo-400 pb-[1px] flex justify-center items-center">
                            <i class="text-lg bi-check2-circle"></i>
                        </div>
                    @elseif($message->status == 2)
                        <div class="rounded-full w-[25px] h-[25px] shrink-0 text-amber-600 pb-[1px] flex justify-center items-center">
                            <i class="text-base bi bi-x-circle"></i>
                        </div>
                    @else
                        <div class="rounded-full w-[25px] h-[25px] shrink-0 text-gray-400 pb-[1px] flex justify-center items-center">
                            <i class="bi-hourglass-split inline-block animate-spin-pause-ease"></i>
                        </div>
                    @endif
                </div>
            </td>

            <!-- Message content -->
            <td class="p-3">
                <div class="line-clamp-2">
                    [{{$this->options[$message->topic]['label']}}] {{$message->remark}}
                </div>
            </td>

            <!-- Date -->
            <td class="font-light p-3"vw-25 >
                <div>{{$message->updated_at->format('d-M-Y, H:i')}}</div>
            </td>

        </tr>
    @endforeach
</table>