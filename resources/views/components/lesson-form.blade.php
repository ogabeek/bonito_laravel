@props(['students'])

<div class="flex gap-4">
    <!-- Calendar on Left -->
    <x-calendar-picker name="class_date" />

    <!-- Right Side: Student, Status, and Details -->
    <div class="flex-1 flex flex-col gap-4">
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
        <div class="completed-section flex flex-col gap-3" x-data="{ showNotes: false }">
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
            
            <div x-show="!showNotes">
                <button type="button" @click="showNotes = true" class="text-xs text-blue-600 hover:underline">+ Add Notes</button>
            </div>
            
            <div x-show="showNotes">
                <label class="form-label">Notes</label>
                <textarea 
                    name="comments" 
                    rows="2" 
                    placeholder="Optional"
                    class="form-input w-full"
                ></textarea>
            </div>
        </div>

        <!-- Student Absent Details -->
        <div class="absent-section hidden">
            <div>
                <label class="form-label">Notes (optional)</label>
                <textarea 
                    name="comments" 
                    rows="2" 
                    placeholder="Why did the student miss?"
                    class="form-input w-full"
                ></textarea>
            </div>
        </div>

        <!-- Teacher Cancelled Details -->
        <div class="cancelled-section hidden">
            <div>
                <label class="form-label">Reason *</label>
                <textarea 
                    name="comments" 
                    rows="2" 
                    placeholder="Why was it cancelled?"
                    class="form-input w-full"
                ></textarea>
            </div>
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
            const absentSection = form.querySelector('.absent-section');
            const cancelledSection = form.querySelector('.cancelled-section');
            
            // Get all required fields
            const topicField = completedSection ? completedSection.querySelector('[name="topic"]') : null;
            const cancelledCommentsField = cancelledSection ? cancelledSection.querySelector('[name="comments"]') : null;
            
            // Hide all sections and remove required
            if (completedSection) {
                completedSection.classList.add('hidden');
                if (topicField) topicField.removeAttribute('required');
            }
            if (absentSection) absentSection.classList.add('hidden');
            if (cancelledSection) {
                cancelledSection.classList.add('hidden');
                if (cancelledCommentsField) cancelledCommentsField.removeAttribute('required');
            }
            
            // Show relevant section and set required
            if (e.target.value === 'completed' && completedSection) {
                completedSection.classList.remove('hidden');
                if (topicField) topicField.setAttribute('required', 'required');
            } else if (e.target.value === 'student_absent' && absentSection) {
                absentSection.classList.remove('hidden');
            } else if (e.target.value === 'teacher_cancelled' && cancelledSection) {
                cancelledSection.classList.remove('hidden');
                if (cancelledCommentsField) cancelledCommentsField.setAttribute('required', 'required');
            }
        }
    });
</script>
@endonce