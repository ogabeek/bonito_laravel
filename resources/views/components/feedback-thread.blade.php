@props(['thread', 'viewer' => 'teacher'])

{{-- Renders a thread's messages as chat bubbles. `viewer` ('teacher'|'admin')
     decides which side is right-aligned (the current user's own messages). --}}
<div class="space-y-2">
    @foreach($thread->messages as $message)
        @php $mine = $message->sender->value === $viewer; @endphp
        <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[80%] rounded-lg px-3 py-2 text-sm {{ $mine ? 'bg-blue-50 text-blue-900' : 'bg-gray-100 text-gray-800' }}">
                <div class="whitespace-pre-wrap break-words [&_a]:underline [&_a]:break-all">{!! Str::linkify($message->body) !!}</div>
                <div class="mt-0.5 text-[10px] text-gray-400">
                    {{ $message->sender->value === 'admin' ? 'Admin' : $thread->teacher->name }} · {{ $message->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
    @endforeach
</div>
