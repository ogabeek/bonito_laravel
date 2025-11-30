@props(['students'])

<div class="flex gap-4">
    <!-- Calendar on Left -->
    <x-calendar-picker name="class_date" />

    <!-- Right Side: Student, Status, and Details -->
    <div class="flex-1 flex flex-col gap-4" x-data>
        <div>
            <div class="flex gap-3 items-end">
                <div class="flex-1">
                    <label class="form-label">Student</label>
                    <select name="student_id" required class="form-input w-full">
                        <option value="">Select...</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <label class="status-btn-completed flex items-center gap-1 border rounded cursor-pointer transition p-2 text-sm font-medium" title="Lesson completed successfully">
                        <input type="radio" name="status" value="completed" class="status-radio hidden" checked>
                        <span>✓ Done</span>
                    </label>
                    
                    <label class="status-btn-absent flex items-center gap-1 border rounded cursor-pointer transition p-2 text-sm font-medium" title="Student was absent">
                        <input type="radio" name="status" value="student_absent" class="status-radio hidden">
                        <span>⚠ SA</span>
                    </label>
                    
                    <label class="status-btn-cancelled flex items-center gap-1 border rounded cursor-pointer transition p-2 text-sm font-medium" title="Cancelled by teacher">
                        <input type="radio" name="status" value="teacher_cancelled" class="status-radio hidden">
                        <span>🚫 CT</span>
                    </label>
                </div>
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
        <div class="comments-section">
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
    // Show/hide details based on status selection
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('status-radio')) {
            const form = e.target.closest('form');
            const completedSection = form.querySelector('.completed-section');
            const commentsField = form.querySelector('[name="comments"]');
            const commentsLabel = form.querySelector('[data-comments-label]');
            
            // Get all required fields
            const topicField = completedSection ? completedSection.querySelector('[name="topic"]') : null;
            
            // Hide completed details by default and remove required
            if (completedSection && topicField) {
                completedSection.classList.add('hidden');
                topicField.removeAttribute('required');
            }
            
            // Show relevant section and set required
            if (e.target.value === 'completed' && completedSection && topicField) {
                completedSection.classList.remove('hidden');
                topicField.setAttribute('required', 'required');
            }

            if (commentsField) {
                if (e.target.value === 'teacher_cancelled') {
                    commentsField.setAttribute('required', 'required');
                    commentsField.placeholder = 'Why was it cancelled?';
                } else {
                    commentsField.removeAttribute('required');
                    commentsField.placeholder = 'Add notes (optional)';
                }
            }

            if (commentsLabel) {
                commentsLabel.textContent = e.target.value === 'teacher_cancelled'
                    ? 'Reason *'
                    : 'Notes (optional)';
            }
        }
    });
</script>
@endonce
