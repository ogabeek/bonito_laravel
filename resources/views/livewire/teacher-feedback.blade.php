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

    public string $newBody = '';

    /** @var array<int, string> Reply body keyed by thread id */
    public array $reply = [];

    private function guard(): void
    {
        abort_unless(session('teacher_id') == $this->teacher->id, 403);
    }

    #[Computed]
    public function threads()
    {
        return $this->teacher->feedbackThreads()
            ->with(['messages', 'teacher'])
            ->latest('updated_at')
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return FeedbackMessage::whereHas('thread', fn ($q) => $q->where('teacher_id', $this->teacher->id))
            ->unreadFrom(FeedbackSender::ADMIN)
            ->count();
    }

    public function openPanel(): void
    {
        $this->guard();
        $this->open = true;

        // Seeing the panel marks the admin's replies as read.
        FeedbackMessage::whereHas('thread', fn ($q) => $q->where('teacher_id', $this->teacher->id))
            ->unreadFrom(FeedbackSender::ADMIN)
            ->update(['read_at' => now()]);

        unset($this->unreadCount, $this->threads);
    }

    public function closePanel(): void
    {
        $this->open = false;
    }

    public function submitNew(): void
    {
        $this->guard();
        $this->validate(['newBody' => 'required|string|max:2000']);

        $thread = $this->teacher->feedbackThreads()->create(['status' => FeedbackStatus::OPEN]);
        $thread->messages()->create([
            'sender' => FeedbackSender::TEACHER,
            'body' => trim($this->newBody),
        ]);

        $this->newBody = '';
        unset($this->threads);
    }

    public function submitReply(int $threadId): void
    {
        $this->guard();
        $this->validate(["reply.$threadId" => 'required|string|max:2000']);

        $thread = $this->teacher->feedbackThreads()->findOrFail($threadId);
        $thread->messages()->create([
            'sender' => FeedbackSender::TEACHER,
            'body' => trim($this->reply[$threadId]),
        ]);
        // A new teacher message brings the thread back to the admin's attention.
        $thread->update(['status' => FeedbackStatus::OPEN]);

        $this->reply[$threadId] = '';
        unset($this->threads);
    }
}; ?>

<div>
    {{-- Trigger --}}
    <button type="button" wire:click="openPanel"
            title="{{ $this->unreadCount > 0 ? $this->unreadCount.' new repl'.($this->unreadCount === 1 ? 'y' : 'ies').' from admin' : 'Feedback & reports' }}"
            class="relative inline-flex items-center gap-1 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800">
        <span>💬 Feedback</span>
        @if($this->unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-2.5 w-2.5">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
            </span>
            <span class="sr-only">{{ $this->unreadCount }} new replies</span>
        @endif
    </button>

    {{-- Modal --}}
    @if($open)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center">
            <div class="absolute inset-0 bg-black/40" wire:click="closePanel"></div>
            <div class="relative z-10 flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white text-left shadow-xl">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Feedback &amp; reports</h3>
                    <button type="button" wire:click="closePanel" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="space-y-4 overflow-y-auto p-4">
                    {{-- New report --}}
                    <form wire:submit="submitNew" class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <label class="block text-xs font-medium text-gray-500">Report a problem or share feedback</label>
                        <textarea wire:model="newBody" rows="3" placeholder="Describe the issue, error, or idea…"
                                  class="w-full rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-2 focus:ring-gray-300"></textarea>
                        @error('newBody') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                        <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Send</button>
                    </form>

                    {{-- Existing threads --}}
                    @forelse($this->threads as $thread)
                        <div class="rounded-lg border border-gray-200">
                            @if($thread->status === \App\Enums\FeedbackStatus::RESOLVED)
                                <div class="border-b border-gray-100 px-3 py-1.5 text-[11px] font-medium text-gray-400">Resolved · reply to reopen</div>
                            @endif
                            <div class="p-3">
                                <x-feedback-thread :thread="$thread" viewer="teacher" />
                            </div>
                            <form wire:submit="submitReply({{ $thread->id }})" class="flex items-center gap-2 border-t border-gray-100 p-2">
                                <input wire:model="reply.{{ $thread->id }}" type="text" placeholder="Reply…"
                                       class="flex-1 rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300">
                                <button type="submit" class="rounded-md bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-900">Send</button>
                            </form>
                            @error("reply.{$thread->id}") <div class="px-3 pb-2 text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-400">No reports yet. Use the box above to send your first one.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
