<?php

use App\Enums\FeedbackSender;
use App\Enums\FeedbackStatus;
use App\Models\FeedbackMessage;
use App\Models\FeedbackThread;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    /** @var array<int, string> Reply body keyed by thread id */
    public array $reply = [];

    private function guard(): void
    {
        abort_unless(session('admin_authenticated'), 403);
    }

    #[Computed]
    public function threads()
    {
        return FeedbackThread::with(['messages', 'teacher'])
            ->orderByRaw("CASE WHEN status = '".FeedbackStatus::OPEN->value."' THEN 0 ELSE 1 END")
            ->latest('updated_at')
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return FeedbackMessage::unreadFrom(FeedbackSender::TEACHER)->count();
    }

    public function submitReply(int $threadId): void
    {
        $this->guard();
        $this->validate(["reply.$threadId" => 'required|string|max:2000']);

        $thread = FeedbackThread::findOrFail($threadId);
        $thread->messages()->create([
            'sender' => FeedbackSender::ADMIN,
            'body' => trim($this->reply[$threadId]),
        ]);

        $this->acknowledge($thread);

        $this->reply[$threadId] = '';
        unset($this->threads, $this->unreadCount);
    }

    public function resolve(int $threadId): void
    {
        $this->guard();
        $thread = FeedbackThread::findOrFail($threadId);
        $this->acknowledge($thread);
        $thread->update(['status' => FeedbackStatus::RESOLVED]);

        unset($this->threads, $this->unreadCount);
    }

    public function reopen(int $threadId): void
    {
        $this->guard();
        FeedbackThread::findOrFail($threadId)->update(['status' => FeedbackStatus::OPEN]);

        unset($this->threads);
    }

    /** Mark the teacher's messages in a thread as read (admin has seen them). */
    private function acknowledge(FeedbackThread $thread): void
    {
        $thread->messages()->unreadFrom(FeedbackSender::TEACHER)->update(['read_at' => now()]);
        $thread->touch();
    }
}; ?>

<div class="mb-6">
    <div class="mb-3 flex items-center gap-2">
        <h2 class="text-xl font-bold">Feedback</h2>
        @if($this->unreadCount > 0)
            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">{{ $this->unreadCount }} new</span>
        @endif
    </div>

    <x-card>
        @forelse($this->threads as $thread)
            @php
                $isResolved = $thread->status === \App\Enums\FeedbackStatus::RESOLVED;
                $hasUnread = $thread->messages
                    ->where('sender', \App\Enums\FeedbackSender::TEACHER)
                    ->whereNull('read_at')
                    ->isNotEmpty();
            @endphp
            <div class="border-b border-gray-100 py-3 last:border-b-0 {{ $isResolved ? 'opacity-60' : '' }}" wire:key="feedback-thread-{{ $thread->id }}">
                <div class="mb-2 flex items-center justify-between gap-2 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-800">{{ $thread->teacher?->name ?? 'Unknown teacher' }}</span>
                        @if($hasUnread)
                            <span class="h-2 w-2 rounded-full bg-red-500" title="New message"></span>
                        @endif
                        @if($isResolved)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">Resolved</span>
                        @endif
                    </div>
                    @if($isResolved)
                        <button type="button" wire:click="reopen({{ $thread->id }})" class="text-xs text-gray-500 hover:text-gray-800">Reopen</button>
                    @else
                        <button type="button" wire:click="resolve({{ $thread->id }})" class="text-xs text-gray-500 hover:text-green-700">Mark resolved</button>
                    @endif
                </div>

                <x-feedback-thread :thread="$thread" viewer="admin" />

                <form wire:submit="submitReply({{ $thread->id }})" class="mt-2 flex items-center gap-2">
                    <input wire:model="reply.{{ $thread->id }}" type="text" placeholder="Reply…"
                           class="flex-1 rounded-md border-gray-200 text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300">
                    <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Send</button>
                </form>
                @error("reply.{$thread->id}") <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
            </div>
        @empty
            <p class="py-4 text-center text-sm text-gray-400">No feedback yet.</p>
        @endforelse
    </x-card>
</div>
