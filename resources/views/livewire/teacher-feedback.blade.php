<?php

use App\Enums\FeedbackSender;
use App\Enums\FeedbackStatus;
use App\Models\FeedbackMessage;
use App\Models\Teacher;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public Teacher $teacher;

    public bool $open = false;

    public string $body = '';

    /**
     * Who the current viewer writes as: the teacher on their own dashboard,
     * or an admin previewing that dashboard (who therefore replies).
     */
    private function acting(): FeedbackSender
    {
        return session('teacher_id') == $this->teacher->id
            ? FeedbackSender::TEACHER
            : FeedbackSender::ADMIN;
    }

    private function counterpart(): FeedbackSender
    {
        return $this->acting() === FeedbackSender::TEACHER ? FeedbackSender::ADMIN : FeedbackSender::TEACHER;
    }

    private function guard(): void
    {
        abort_unless(session('teacher_id') == $this->teacher->id || session('admin_authenticated'), 403);
    }

    /** Bubble alignment for <x-feedback-thread>: the viewer's own messages sit right. */
    #[Computed]
    public function viewer(): string
    {
        return $this->acting()->value;
    }

    /** The teacher's single ongoing conversation with the admin (if any). */
    #[Computed]
    public function thread()
    {
        return $this->teacher->feedbackThreads()
            ->with(['messages', 'teacher'])
            ->latest('updated_at')
            ->first();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return FeedbackMessage::whereHas('thread', fn ($q) => $q->where('teacher_id', $this->teacher->id))
            ->unreadFrom($this->counterpart())
            ->count();
    }

    public function togglePanel(): void
    {
        $this->guard();
        $this->open = ! $this->open;

        if ($this->open) {
            // Opening the chat marks the other side's messages as read.
            FeedbackMessage::whereHas('thread', fn ($q) => $q->where('teacher_id', $this->teacher->id))
                ->unreadFrom($this->counterpart())
                ->update(['read_at' => now()]);

            unset($this->unreadCount, $this->thread);
        }
    }

    public function send(): void
    {
        $this->guard();
        $this->validate(['body' => 'required|string|max:2000']);

        $thread = $this->thread ?? $this->teacher->feedbackThreads()->create(['status' => FeedbackStatus::OPEN]);
        $thread->messages()->create([
            'sender' => $this->acting(),
            'body' => trim($this->body),
        ]);
        // A new message keeps the conversation active.
        $thread->update(['status' => FeedbackStatus::OPEN]);

        $this->body = '';
        unset($this->thread);
    }
}; ?>

<div class="relative">
    {{-- Trigger --}}
    <button type="button" wire:click="togglePanel"
            title="{{ $this->unreadCount > 0 ? $this->unreadCount.' new message'.($this->unreadCount === 1 ? '' : 's') : 'Help improve this teacher space' }}"
            class="relative inline-flex items-center gap-1 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">
        <span>Help improve this space</span>
        @if($this->unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-2.5 w-2.5">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
            </span>
            <span class="sr-only">{{ $this->unreadCount }} new messages</span>
        @endif
    </button>

    {{-- Messenger popover --}}
    @if($open)
        <div class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl"
             x-data x-on:keydown.escape.window="$wire.set('open', false)">
            <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                <span class="text-sm font-semibold text-gray-800">
                    {{ $this->viewer === 'teacher' ? 'Share your experience' : 'Reply to '.$this->teacher->name }}
                </span>
                <button type="button" wire:click="$set('open', false)" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            @if($this->viewer === 'teacher')
                <div class="border-b border-gray-100 bg-gray-50 px-3 py-2 text-xs leading-snug text-gray-600">
                    This platform is under development. Your feedback helps us improve this teacher space. Leave a comment here; we read every message.
                </div>
            @endif

            <div class="max-h-72 space-y-2 overflow-y-auto p-3" x-init="$el.scrollTop = $el.scrollHeight">
                @if($this->thread)
                    <x-feedback-thread :thread="$this->thread" :viewer="$this->viewer" />
                @else
                    <p class="py-6 text-center text-xs text-gray-400">No messages yet.</p>
                @endif
            </div>

            <form wire:submit="send" class="flex items-center gap-2 border-t border-gray-100 p-2">
                <input wire:model="body" type="text" placeholder="Type a message…" autofocus
                       class="min-w-0 flex-1 rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300">
                <button type="submit" class="shrink-0 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Send</button>
            </form>
            @error('body') <div class="px-3 pb-2 text-xs text-red-600">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
