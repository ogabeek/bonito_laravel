@props(['students'])

<div class="flex flex-col lg:flex-row gap-4">
    <!-- Calendar on Left (Desktop) / Top (Mobile) -->
    <x-calendar-picker name="class_date" />

    <!-- Right Side: Student, Status, and Details -->
    <div class="flex-1 flex flex-col gap-3 lg:gap-4 lg:min-h-[210px]" x-data>
        <!-- Student Select -->
        <div>
            <label class="form-label">Student</label>
            <select name="student_id" required class="form-input w-full">
                <option value="">Select...</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Status Buttons - Mobile Optimized -->
        <div>
            <label class="form-label">Status</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                <label class="status-btn-completed flex items-center justify-center border rounded cursor-pointer transition p-3 text-sm font-medium" title="Lesson completed successfully">
                    <input type="radio" name="status" value="completed" class="status-radio hidden" checked>
                    <span>✓ Done</span>
                </label>

                <label class="status-btn-student-cancelled flex items-center justify-center border rounded cursor-pointer transition p-3 text-sm font-medium" title="Cancelled by student/parent (agreed)">
                    <input type="radio" name="status" value="student_cancelled" class="status-radio hidden">
                    <span>C</span>
                </label>
                
                <label class="status-btn-cancelled flex items-center justify-center border rounded cursor-pointer transition p-3 text-sm font-medium" title="Cancelled by teacher">
                    <input type="radio" name="status" value="teacher_cancelled" class="status-radio hidden">
                    <span>CT</span>
                </label>

                <label class="status-btn-absent flex items-center justify-center border rounded cursor-pointer transition p-3 text-sm font-medium" title="Student was absent">
                    <input type="radio" name="status" value="student_absent" class="status-radio hidden">
                    <span>A</span>
                </label>
            </div>
        </div>

        <!-- Completed Details -->
        <div class="completed-section flex flex-col gap-3">
            <div>
                <label class="form-label">Topic *</label>
                <input 
                    type="text" 
                    name="topic" 
                    required
                    placeholder="What was taught?"
                    class="form-input w-full"
                >
            </div>
            
            <div>
                <label class="form-label">Homework</label>
                <textarea 
                    name="homework" 
                    rows="2" 
                    placeholder="Optional"
                    class="form-input w-full"
                ></textarea>
            </div>
        </div>

        <!-- Shared notes/reason field -->
        <div class="comments-toggle text-xs">
            <button type="button" data-comments-toggle class="text-blue-600 hover:underline">+ Add notes</button>
        </div>
        <div class="comments-section hidden" data-comments-section>
            <label class="form-label" data-comments-label>Notes (optional)</label>
            <textarea 
                name="comments" 
                rows="2" 
                placeholder="Add notes or cancellation reason"
                class="form-input w-full"
                data-comments-field
            ></textarea>
        </div>
    </div>
</div>

@once
<script>
    const updateCommentsUI = (form, status) => {
        const completedSection = form.querySelector('.completed-section');
        const commentsField = form.querySelector('[name="comments"]');
        const commentsLabel = form.querySelector('[data-comments-label]');
        const commentsSection = form.querySelector('[data-comments-section]');
        const commentsToggle = form.querySelector('[data-comments-toggle]');
        const topicField = completedSection ? completedSection.querySelector('[name="topic"]') : null;

        // Completed fields visibility/required
        if (completedSection && topicField) {
            const showCompleted = status === 'completed';
            completedSection.classList.toggle('hidden', !showCompleted);
            topicField.toggleAttribute('required', showCompleted);
        }

        // Comments field requirements and visibility
        if (commentsField) {
            const isTeacherCancelled = status === 'teacher_cancelled';
            commentsField.toggleAttribute('required', isTeacherCancelled);
            commentsField.placeholder = isTeacherCancelled ? 'Why was it cancelled?' : 'Add notes (optional)';
        }

        if (commentsLabel) {
            commentsLabel.textContent = status === 'teacher_cancelled' ? 'Reason *' : 'Notes (optional)';
        }

        if (commentsSection && commentsToggle) {
            if (status === 'teacher_cancelled') {
                commentsSection.classList.remove('hidden');
                commentsToggle.classList.add('hidden');
            } else if (status === 'completed') {
                commentsSection.classList.add('hidden');
                commentsToggle.classList.remove('hidden');
            } else {
                commentsSection.classList.remove('hidden');
                commentsToggle.classList.add('hidden');
            }
        }
    };

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('newLessonForm');
        const checked = form?.querySelector('.status-radio:checked');
        if (form && checked) updateCommentsUI(form, checked.value);
    });

    // Show/hide details based on status selection
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('status-radio')) {
            const form = e.target.closest('form');
            updateCommentsUI(form, e.target.value);
        }
    });

    // Toggle optional notes visibility (for "Done" state)
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-comments-toggle]')) {
            const form = e.target.closest('form');
            const commentsSection = form?.querySelector('[data-comments-section]');
            if (commentsSection) {
                commentsSection.classList.remove('hidden');
                e.target.classList.add('hidden');
            }
        }
    });
</script>
@endonce
