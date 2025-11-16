<div class="space-y-3">
    @if($isNew)
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="class_date" value="{{ now()->format('Y-m-d') }}" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Student</label>
                <select name="student_id" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select...</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @else
        <div class="text-xs text-gray-600 mb-2 bg-gray-50 px-2 py-1.5 rounded">
            <strong>{{ $lesson->class_date->format('M d, Y') }}</strong> · {{ $lesson->student->name }}
        </div>
    @endif
    
    <div>
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
        <div class="grid grid-cols-3 gap-2">
            <label class="flex items-center justify-center px-3 py-2 border rounded cursor-pointer transition hover:bg-gray-50 has-[:checked]:bg-green-50 has-[:checked]:border-green-500 has-[:checked]:text-green-700">
                <input 
                    type="radio" 
                    name="status" 
                    value="completed" 
                    class="status-radio hidden" 
                    {{ ($isNew || (!$isNew && $lesson->status === 'completed')) ? 'checked' : '' }}
                >
                <span class="text-sm font-medium">✓ Done</span>
            </label>
            
            <label class="flex items-center justify-center px-3 py-2 border rounded cursor-pointer transition hover:bg-gray-50 has-[:checked]:bg-red-50 has-[:checked]:border-red-500 has-[:checked]:text-red-700">
                <input 
                    type="radio" 
                    name="status" 
                    value="student_absent" 
                    class="status-radio hidden" 
                    {{ (!$isNew && $lesson->status === 'student_absent') ? 'checked' : '' }}
                >
                <span class="text-sm font-medium">⚠ Absent</span>
            </label>
            
            <label class="flex items-center justify-center px-3 py-2 border rounded cursor-pointer transition hover:bg-gray-50 has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500 has-[:checked]:text-orange-700">
                <input 
                    type="radio" 
                    name="status" 
                    value="teacher_cancelled" 
                    class="status-radio hidden" 
                    {{ (!$isNew && $lesson->status === 'teacher_cancelled') ? 'checked' : '' }}
                >
                <span class="text-sm font-medium">🚫 Cancel</span>
            </label>
        </div>
    </div>
    
    <!-- Completed Details -->
    <div class="completed-section space-y-2" style="{{ $isNew || (!$isNew && $lesson->status === 'completed') ? '' : 'display:none' }}">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Topic *</label>
            <input 
                type="text" 
                name="topic" 
                value="{{ $isNew ? '' : $lesson->topic }}" 
                {{ ($isNew || (!$isNew && $lesson->status === 'completed')) ? 'required' : '' }}
                placeholder="What was taught?"
                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Homework</label>
            <textarea 
                name="homework" 
                rows="2" 
                placeholder="Optional"
                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >{{ $isNew ? '' : $lesson->homework }}</textarea>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
            <textarea 
                name="comments" 
                rows="2" 
                placeholder="Optional"
                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >{{ $isNew ? '' : $lesson->comments }}</textarea>
        </div>
    </div>

    <!-- Student Absent Details -->
    <div class="absent-section" style="{{ !$isNew && $lesson->status === 'student_absent' ? '' : 'display:none' }}">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Notes (optional)</label>
            <textarea 
                name="comments" 
                rows="2" 
                placeholder="Why did the student miss?"
                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >{{ $isNew ? '' : $lesson->comments }}</textarea>
        </div>
    </div>

    <!-- Teacher Cancelled Details -->
    <div class="cancelled-section" style="{{ !$isNew && $lesson->status === 'teacher_cancelled' ? '' : 'display:none' }}">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Reason *</label>
            <textarea 
                name="comments" 
                rows="2" 
                placeholder="Why was it cancelled?"
                {{ (!$isNew && $lesson->status === 'teacher_cancelled') ? 'required' : '' }}
                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >{{ $isNew ? '' : $lesson->comments }}</textarea>
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
                completedSection.style.display = 'none';
                if (topicField) topicField.removeAttribute('required');
            }
            if (absentSection) absentSection.style.display = 'none';
            if (cancelledSection) {
                cancelledSection.style.display = 'none';
                if (cancelledCommentsField) cancelledCommentsField.removeAttribute('required');
            }
            
            // Show relevant section and set required
            if (e.target.value === 'completed' && completedSection) {
                completedSection.style.display = 'block';
                if (topicField) topicField.setAttribute('required', 'required');
            } else if (e.target.value === 'student_absent' && absentSection) {
                absentSection.style.display = 'block';
            } else if (e.target.value === 'teacher_cancelled' && cancelledSection) {
                cancelledSection.style.display = 'block';
                if (cancelledCommentsField) cancelledCommentsField.setAttribute('required', 'required');
            }
        }
    });
</script>
@endonce