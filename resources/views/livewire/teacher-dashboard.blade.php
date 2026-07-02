<?php
/**
 * Livewire v4 Functional Component: Teacher Dashboard
 * Replaces: teacher-dashboard.js AJAX handling
 * Handles: lesson CRUD, month navigation, stats display
 */

use App\Models\Teacher;
use App\Models\Lesson;
use App\Enums\StudentStatus;
use App\Concerns\LogsActivityActions;
use App\Repositories\LessonRepository;
use App\Services\LessonService;
use App\Services\LessonStatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
    public bool $refund_requested = false;

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

    /**
     * Per-student status hint, independent of the viewed month:
     *  - 'inactivity'  : an Active student with no lesson in the last 7 days.
     *  - 'reactivation': a non-active student whose classes have resumed.
     * Students needing no action are omitted.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    #[Computed]
    public function studentNudges(): \Illuminate\Support\Collection
    {
        $students = $this->students;
        $latest = app(LessonRepository::class)->getLatestLessonDateByStudent($students->pluck('id')->all());
        $threshold = now()->subDays(7)->startOfDay();

        return $students->mapWithKeys(function ($student) use ($latest, $threshold) {
            $last = isset($latest[$student->id]) ? Carbon::parse($latest[$student->id]) : null;
            $recent = $last !== null && $last->gte($threshold);

            if ($student->status === StudentStatus::ACTIVE) {
                // Was attending (has history) but has now gone quiet for a week.
                $nudge = ($last !== null && ! $recent) ? 'inactivity' : null;
            } else {
                // Any non-active student with classes again → suggest reactivating.
                $nudge = $recent ? 'reactivation' : null;
            }

            return [$student->id => $nudge];
        })->filter();
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
        $this->guard();

        // Clear any lingering confirmation so a failed submit shows the error, not stale success.
        $this->showSuccess = false;

        $this->validate([
            'student_id' => 'required|exists:students,id',
            'class_date' => 'required|date',
            'status' => 'required|in:completed,student_cancelled,teacher_cancelled,student_absent',
            'topic' => 'required_if:status,completed|nullable|string|max:500',
            'homework' => 'nullable|string|max:1000',
            'comments' => 'required_if:status,teacher_cancelled,student_absent|nullable|string|max:1000',
        ], [
            'comments.required_if' => 'A note is required when cancelling a lesson or marking a student absent.',
        ]);

        // Verify student belongs to this teacher
        $assigned = $this->teacher->students()->where('students.id', $this->student_id)->exists();
        if (!$assigned) {
            $this->addError('student_id', 'Student not assigned to you');
            return;
        }

        // Topic/homework only apply to completed lessons; the form hides those
        // fields for other statuses, so drop any stale form state here.
        app(LessonService::class)->create([
            'student_id' => $this->student_id,
            'class_date' => $this->class_date,
            'status' => $this->status,
            'topic' => $this->status === 'completed' ? ($this->topic ?: '') : '',
            'homework' => $this->status === 'completed' ? ($this->homework ?: null) : null,
            'comments' => $this->comments ?: null,
            'absence_reminder_sent' => $this->absence_reminder_sent,
            'absence_chat_notified' => $this->absence_chat_notified,
            'refund_requested' => $this->refund_requested,
        ], $this->teacher);

        // Reset form but keep student and date for convenience
        $keepStudent = $this->student_id;
        $keepDate = $this->class_date;
        $this->reset(['topic', 'homework', 'comments', 'absence_reminder_sent', 'absence_chat_notified', 'refund_requested']);
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
        $this->guard();

        $lesson = Lesson::find($lessonId);

        if (!$lesson || $lesson->teacher_id !== $this->teacher->id) {
            return;
        }

        app(LessonService::class)->delete($lesson, $this->teacher);

        // Clear computed cache
        unset($this->lessons, $this->stats, $this->studentStats);

        $this->showSuccess = true;
        $this->successMessage = 'Lesson deleted.';
    }

    public function dismissSuccess(): void
    {
        $this->showSuccess = false;
    }

    /**
     * Change an assigned student's status from the list (via the status dot).
     * Holiday carries a vacation date range; any non-active status carries a note.
     */
    public function saveStudentStatus(int $studentId, string $status, ?string $start = null, ?string $end = null, ?string $note = null): void
    {
        $this->guard();

        $student = $this->teacher->students()->find($studentId);
        if (! $student) {
            return;
        }

        $validated = Validator::make(compact('status', 'start', 'end', 'note'), [
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $from = $student->status->value;
        $status = StudentStatus::from($validated['status']);

        $student->changeStatus($status, $validated['start'], $validated['end'], $validated['note']);

        $this->logActivity(
            $student,
            'student_status_updated',
            ['from' => $from, 'to' => $status->value, 'note' => $student->status_note],
            $this->teacher
        );

        unset($this->students, $this->studentNudges);
    }

    private function guard(): void
    {
        abort_unless(
            session('admin_authenticated') || (int) session('teacher_id') === $this->teacher->id,
            403
        );
    }
};
?>

<div>
    <div class="p-3 sm:p-6 max-w-5xl mx-auto">

        <x-page-header
            :title="$this->teacher->name . '\'s Dashboard'"
            :logoutRoute="route('teacher.logout')"
        >
            <livewire:teacher-feedback :teacher="$this->teacher" />
        </x-page-header>

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
                <x-student-stats-list :students="$this->students" :stats="$this->studentStats" :totalStats="$this->stats" :showBalance="false" :editable="true" :nudges="$this->studentNudges" />
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
                                @foreach([
                                    [\App\Enums\LessonStatus::COMPLETED, 'Done', 'Lesson completed successfully'],
                                    [\App\Enums\LessonStatus::STUDENT_CANCELLED, 'C', 'Cancelled by student/parent'],
                                    [\App\Enums\LessonStatus::TEACHER_CANCELLED, 'CT', 'Cancelled by teacher'],
                                    [\App\Enums\LessonStatus::STUDENT_ABSENT, 'A', 'Student was absent'],
                                ] as [$statusOption, $statusCode, $statusTitle])
                                    <label class="flex cursor-pointer items-center justify-center rounded border px-3 py-2 text-sm font-medium transition {{ $status === $statusOption->value ? $statusOption->badgeClass() : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}" title="{{ $statusTitle }}">
                                        <input type="radio" wire:model.live="status" value="{{ $statusOption->value }}" class="hidden">
                                        <span>{{ $statusCode }}</span>
                                    </label>
                                @endforeach
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
                                    <span>Reminder sent before class</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" wire:model="absence_chat_notified" class="form-checkbox">
                                    <span>No response after waiting message</span>
                                </label>
                                <p class="text-xs text-gray-500 italic">If both follow-up steps were completed, the school can recover 50% of the lesson payment.</p>
                                <label class="flex items-center gap-2 text-sm cursor-pointer mt-1 pt-2 border-t border-gray-100">
                                    <input type="checkbox" wire:model="refund_requested" class="form-checkbox">
                                    <span>Needs recovery</span>
                                </label>
                            </div>
                        @endif

                        {{-- Comments/Reason --}}
                        @if($status === 'teacher_cancelled')
                            <div>
                                <label class="form-label">Reason *</label>
                                <textarea wire:model="comments" rows="2" placeholder="Why was it cancelled?" class="form-input w-full" required></textarea>
                            </div>
                        @elseif($status === 'student_absent')
                            <div>
                                <label class="form-label">Notes *</label>
                                <textarea wire:model="comments" rows="2" placeholder="What happened?" class="form-input w-full" required></textarea>
                            </div>
                        @elseif($status === 'student_cancelled')
                            <div>
                                <label class="form-label">Notes (optional)</label>
                                <textarea wire:model="comments" rows="2" placeholder="Add notes" class="form-input w-full"></textarea>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 sm:mt-6 flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn-primary w-full sm:w-auto">+ Add Class</button>
                    @if($this->showSuccess)
                        <span wire:key="form-status-ok" x-data x-init="setTimeout(() => $wire.dismissSuccess(), 4000)"
                              class="inline-flex items-center gap-1 text-sm font-medium text-green-700">✓ {{ $this->successMessage }}</span>
                    @elseif($errors->any())
                        <span class="text-sm font-medium text-red-600">{{ $errors->first() }}</span>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Lessons List --}}
        <x-card title="Lessons">
            @if($this->lessons->count() > 0)
                <div class="space-y-2">
                    @foreach($this->lessons as $lesson)
                        <div class="group relative">
                            <x-lesson-card :lesson="$lesson" :showStudent="true" :neutralNonCompleted="true" :showAbsenceFollowUp="true" />
                            <button
                                wire:click="deleteLesson({{ $lesson->id }})"
                                wire:confirm="Are you sure you want to delete this lesson? This cannot be undone."
                                class="absolute right-1 top-1 w-5 h-5 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                                title="Delete"
                                aria-label="Delete lesson"
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
