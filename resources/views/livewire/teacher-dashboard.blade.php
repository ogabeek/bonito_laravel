<?php
/**
 * Livewire v4 Functional Component: Teacher Dashboard
 * Replaces: teacher-dashboard.js AJAX handling
 * Handles: lesson CRUD, month navigation, stats display
 */

use App\Models\Teacher;
use App\Models\Lesson;
use App\Concerns\LogsActivityActions;
use App\Repositories\LessonRepository;
use App\Services\LessonStatisticsService;
use App\Services\CalendarService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    use LogsActivityActions;

    public Teacher $teacher;
    public string $monthParam = '';

    // Form fields
    public string $student_id = '';
    public string $class_date = '';
    public string $status = 'completed';
    public string $topic = '';
    public string $homework = '';
    public string $comments = '';
    public bool $absence_reminder_sent = false;
    public bool $absence_chat_notified = false;

    // UI state
    public bool $showSuccess = false;
    public string $successMessage = '';

    public function mount(Teacher $teacher): void
    {
        $this->teacher = $teacher;
        $this->monthParam = request()->query('month', now()->format('Y-m'));
        $this->class_date = now()->format('Y-m-d');
    }

    public function getDate(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->monthParam)->startOfMonth();
        } catch (\Exception $e) {
            return now()->startOfMonth();
        }
    }

    public function getPrevMonth(): Carbon
    {
        return $this->getDate()->copy()->subMonth();
    }

    public function getNextMonth(): Carbon
    {
        return $this->getDate()->copy()->addMonth();
    }

    #[Computed]
    public function students()
    {
        return $this->teacher->students()->orderBy('name')->get();
    }

    #[Computed]
    public function lessons()
    {
        return app(LessonRepository::class)->getForTeacher($this->teacher->id, $this->getDate());
    }

    #[Computed]
    public function stats(): array
    {
        return app(LessonStatisticsService::class)->calculateStats($this->lessons);
    }

    #[Computed]
    public function studentStats(): \Illuminate\Support\Collection
    {
        return app(LessonStatisticsService::class)->calculateStatsByStudent($this->lessons);
    }

    public function goToMonth(string $direction): void
    {
        if ($direction === 'prev') {
            $this->monthParam = $this->getPrevMonth()->format('Y-m');
        } else {
            $this->monthParam = $this->getNextMonth()->format('Y-m');
        }
        // Clear computed cache for lessons/stats
        unset($this->lessons, $this->stats, $this->studentStats);
    }

    public function createLesson(): void
    {
        $this->validate([
            'student_id' => 'required|exists:students,id',
            'class_date' => 'required|date',
            'status' => 'required|in:completed,student_cancelled,teacher_cancelled,student_absent',
            'topic' => 'required_if:status,completed|nullable|string|max:500',
            'homework' => 'nullable|string|max:1000',
            'comments' => 'required_if:status,teacher_cancelled|nullable|string|max:1000',
        ]);

        // Verify student belongs to this teacher
        $assigned = $this->teacher->students()->where('students.id', $this->student_id)->exists();
        if (!$assigned) {
            $this->addError('student_id', 'Student not assigned to you');
            return;
        }

        $lesson = Lesson::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student_id,
            'class_date' => $this->class_date,
            'status' => $this->status,
            'topic' => $this->status === 'completed' ? ($this->topic ?: '') : '',
            'homework' => $this->status === 'completed' ? $this->homework : null,
            'comments' => $this->comments ?: null,
            'absence_reminder_sent' => $this->absence_reminder_sent,
            'absence_chat_notified' => $this->absence_chat_notified,
        ]);

        $lesson->load('student');

        $this->logActivity(
            $lesson,
            'lesson_created',
            [
                'student_id' => $this->student_id,
                'class_date' => $this->class_date,
                'status' => $this->status,
                'topic' => $this->topic,
                'homework' => $this->homework,
                'comments' => $this->comments,
            ],
            $this->teacher
        );

        // Reset form but keep student and date for convenience
        $keepStudent = $this->student_id;
        $keepDate = $this->class_date;
        $this->reset(['topic', 'homework', 'comments', 'absence_reminder_sent', 'absence_chat_notified']);
        $this->status = 'completed';
        $this->student_id = $keepStudent;
        $this->class_date = $keepDate;

        // Clear computed cache to refresh lessons list
        unset($this->lessons, $this->stats, $this->studentStats);

        $this->showSuccess = true;
        $this->successMessage = 'Lesson added successfully!';
    }

    public function deleteLesson(int $lessonId): void
    {
        $lesson = Lesson::find($lessonId);

        if (!$lesson || $lesson->teacher_id !== $this->teacher->id) {
            return;
        }

        $lesson->load('student');
        $snapshot = $lesson->toArray();

        $this->logActivity(
            $lesson,
            'lesson_deleted',
            ['snapshot' => $snapshot],
            $this->teacher
        );

        $lesson->delete();

        // Clear computed cache
        unset($this->lessons, $this->stats, $this->studentStats);

        $this->showSuccess = true;
        $this->successMessage = 'Lesson deleted.';
    }

    public function dismissSuccess(): void
    {
        $this->showSuccess = false;
    }
};
?>

<div>
    <div class="p-3 sm:p-6 max-w-5xl mx-auto">

        <x-page-header
            :title="$this->teacher->name . \"'s Dashboard\""
            :logoutRoute="route('teacher.logout')"
        />

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm px-3 py-2 rounded">
                <ul class="list-disc pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success Message --}}
        @if($this->showSuccess)
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-xs sm:text-sm px-3 py-2 rounded flex justify-between items-center">
                <span>{{ $this->successMessage }}</span>
                <button wire:click="dismissSuccess" class="text-green-700 hover:text-green-900 font-bold">&times;</button>
            </div>
        @endif

        @if(config('banners.teacher_info.enabled'))
            <x-info-banner :type="config('banners.teacher_info.type')" id="teacher_info" class="mb-4">
                {{ config('banners.teacher_info.message') }}
            </x-info-banner>
        @endif

        @if(config('banners.teacher_howto.enabled'))
            <x-info-banner :type="config('banners.teacher_howto.type')" id="teacher_howto" class="mb-6">
                <div class="font-medium mb-1">{{ config('banners.teacher_howto.title') }}</div>
                <div class="text-xs opacity-90">{!! config('banners.teacher_howto.message') !!}</div>
            </x-info-banner>
        @endif

        {{-- Student stats with month navigation --}}
        <x-card class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-3 sm:gap-4 mb-3">
                <div class="flex items-center gap-2 sm:gap-3">
                    <h2 class="text-lg sm:text-xl font-semibold">{{ $this->getDate()->format('F Y') }}</h2>
                    <div class="flex items-center gap-1">
                        <button wire:click="goToMonth('prev')" class="px-2 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded" title="Previous month">&larr;</button>
                        <button wire:click="goToMonth('next')" class="px-2 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded" title="Next month">&rarr;</button>
                    </div>
                </div>
            </div>
            @if($this->students->count() > 0)
                <x-student-stats-list :students="$this->students" :stats="$this->studentStats" :totalStats="$this->stats" :showBalance="false" />
            @else
                <x-empty-state message="No students assigned" />
            @endif
        </x-card>

        {{-- Lesson Form --}}
        <x-card class="mb-6">
            <form wire:submit="createLesson">
                <div class="flex flex-col lg:flex-row gap-4">
                    {{-- Calendar - using Alpine with Livewire binding --}}
                    <div class="calendar-container flex-shrink-0" x-data="{
                        date: new Date(),
                        selected: $wire.entangle('class_date'),
                        today: '{{ now()->format('Y-m-d') }}',
                        get month() { return this.date.getMonth() },
                        get year() { return this.date.getFullYear() },
                        get days() {
                            let first = new Date(this.year, this.month, 1).getDay();
                            first = (first === 0 ? 6 : first - 1);
                            let last = new Date(this.year, this.month + 1, 0).getDate();
                            return Array(first).fill(0).concat([...Array(last)].map((_, i) => i + 1));
                        },
                        fmt(d) {
                            let m = String(this.month + 1).padStart(2, '0');
                            let day = String(d).padStart(2, '0');
                            return `${this.year}-${m}-${day}`;
                        },
                        isToday(d) { return this.fmt(d) === this.today; }
                    }">
                        <div class="border rounded" style="padding: var(--spacing-sm);">
                            <div class="flex justify-between items-center" style="margin-bottom: var(--spacing-sm);">
                                <button type="button" @click="date = new Date(year, month - 1)" style="padding: var(--spacing-xs);" class="hover:bg-gray-100 rounded">←</button>
                                <span style="font-weight: var(--font-weight-medium);" x-text="date.toLocaleDateString('en-US', {month:'short', year:'numeric'})"></span>
                                <button type="button" @click="date = new Date(year, month + 1)" style="padding: var(--spacing-xs);" class="hover:bg-gray-100 rounded">→</button>
                            </div>
                            <div class="grid grid-cols-7 gap-0.5 text-center">
                                <template x-for="day in ['M','T','W','T','F','S','S']">
                                    <div style="color: var(--color-text-secondary);" class="p-0.5 text-xs" x-text="day"></div>
                                </template>
                                <template x-for="(d, index) in days" :key="index">
                                    <div class="aspect-square relative">
                                        <button
                                            x-show="d > 0"
                                            type="button"
                                            @click="selected = fmt(d)"
                                            :class="{
                                                'ring-1 ring-blue-400': isToday(d) && selected !== fmt(d),
                                                'bg-blue-600 text-white font-semibold': selected === fmt(d)
                                            }"
                                            class="p-0.5 rounded aspect-square hover:bg-gray-100 w-full h-full transition text-sm"
                                            x-text="d">
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Right Side: Student, Status, Details --}}
                    <div class="flex-1 flex flex-col gap-3 lg:gap-4 lg:min-h-[210px]">
                        {{-- Student Select --}}
                        <div>
                            <label class="form-label">Student</label>
                            <select wire:model="student_id" required class="form-input w-full">
                                <option value="">Select...</option>
                                @foreach($this->students as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status Buttons --}}
                        <div>
                            <label class="form-label">Status</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                                <label class="flex items-center justify-center border rounded cursor-pointer transition px-3 py-2 text-sm font-medium {{ $status === 'completed' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-white border-gray-300 hover:bg-gray-50' }}" title="Lesson completed successfully">
                                    <input type="radio" wire:model.live="status" value="completed" class="hidden">
                                    <span>✓ Done</span>
                                </label>
                                <label class="flex items-center justify-center border rounded cursor-pointer transition px-3 py-2 text-sm font-medium {{ $status === 'student_cancelled' ? 'bg-yellow-100 border-yellow-400 text-yellow-700' : 'bg-white border-gray-300 hover:bg-gray-50' }}" title="Cancelled by student/parent">
                                    <input type="radio" wire:model.live="status" value="student_cancelled" class="hidden">
                                    <span>C</span>
                                </label>
                                <label class="flex items-center justify-center border rounded cursor-pointer transition px-3 py-2 text-sm font-medium {{ $status === 'teacher_cancelled' ? 'bg-orange-100 border-orange-400 text-orange-700' : 'bg-white border-gray-300 hover:bg-gray-50' }}" title="Cancelled by teacher">
                                    <input type="radio" wire:model.live="status" value="teacher_cancelled" class="hidden">
                                    <span>CT</span>
                                </label>
                                <label class="flex items-center justify-center border rounded cursor-pointer transition px-3 py-2 text-sm font-medium {{ $status === 'student_absent' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-white border-gray-300 hover:bg-gray-50' }}" title="Student was absent">
                                    <input type="radio" wire:model.live="status" value="student_absent" class="hidden">
                                    <span>A</span>
                                </label>
                            </div>
                        </div>

                        {{-- Completed fields --}}
                        @if($status === 'completed')
                            <div class="flex flex-col gap-3">
                                <div>
                                    <label class="form-label">Topic *</label>
                                    <input type="text" wire:model="topic" placeholder="What was taught?" class="form-input w-full" required>
                                </div>
                                <div>
                                    <label class="form-label">Homework</label>
                                    <textarea wire:model="homework" rows="2" placeholder="Optional" class="form-input w-full"></textarea>
                                </div>
                            </div>
                        @endif

                        {{-- Absent fields --}}
                        @if($status === 'student_absent')
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" wire:model="absence_reminder_sent" class="form-checkbox">
                                    <span>Reminder was sent before class</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" wire:model="absence_chat_notified" class="form-checkbox">
                                    <span>Texted to chat about waiting, no response</span>
                                </label>
                                <p class="text-xs text-gray-500 italic">If both steps were completed, the school recovers 50% of the lesson payment.</p>
                            </div>
                        @endif

                        {{-- Comments/Reason --}}
                        @if($status === 'teacher_cancelled')
                            <div>
                                <label class="form-label">Reason *</label>
                                <textarea wire:model="comments" rows="2" placeholder="Why was it cancelled?" class="form-input w-full" required></textarea>
                            </div>
                        @elseif($status === 'student_cancelled' || $status === 'student_absent')
                            <div>
                                <label class="form-label">Notes (optional)</label>
                                <textarea wire:model="comments" rows="2" placeholder="Add notes" class="form-input w-full"></textarea>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 sm:mt-6">
                    <button type="submit" class="btn-primary w-full sm:w-auto">+ Add Class</button>
                </div>
            </form>
        </x-card>

        {{-- Lessons List --}}
        <x-card :title="'📚 Lessons (' . $this->stats['total'] . ')'">
            @if($this->lessons->count() > 0)
                <div class="space-y-2">
                    @foreach($this->lessons as $lesson)
                        <div class="group relative">
                            <x-lesson-card :lesson="$lesson" :showStudent="true" :showDelete="false" />
                            <button
                                wire:click="deleteLesson({{ $lesson->id }})"
                                wire:confirm="Are you sure you want to delete this lesson? This cannot be undone."
                                class="absolute right-1 top-1 w-5 h-5 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                                title="Delete"
                            >🗑</button>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state message="No lessons this month" />
            @endif
        </x-card>

    </div>
</div>
