<div class="space-y-3">
    @if($isNew)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" name="class_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
            <select name="student_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="">Select Student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div class="text-sm text-gray-600 mb-2">
            <strong>Date:</strong> {{ $lesson->class_date->format('D, M d, Y') }} | 
            <strong>Student:</strong> {{ $lesson->student->name }}
        </div>
    @endif
    
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
        <div class="flex gap-2 flex-wrap">
            <label class="flex items-center px-4 py-2 border rounded-md cursor-pointer hover:bg-gray-50 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                <input 
                    type="radio" 
                    name="status" 
                    value="completed" 
                    class="status-radio mr-2" 
                    {{ ($isNew || (!$isNew && $lesson->status === 'completed')) ? 'checked' : '' }}
                >
                <span class="text-sm">✓ Completed</span>
            </label>
            
            <label class="flex items-center px-4 py-2 border rounded-md cursor-pointer hover:bg-gray-50 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                <input 
                    type="radio" 
                    name="status" 
                    value="student_absent" 
                    class="status-radio mr-2" 
                    {{ (!$isNew && $lesson->status === 'student_absent') ? 'checked' : '' }}
                >
                <span class="text-sm">⚠ Student Absent</span>
            </label>
            
            <label class="flex items-center px-4 py-2 border rounded-md cursor-pointer hover:bg-gray-50 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                <input 
                    type="radio" 
                    name="status" 
                    value="teacher_cancelled" 
                    class="status-radio mr-2" 
                    {{ (!$isNew && $lesson->status === 'teacher_cancelled') ? 'checked' : '' }}
                >
                <span class="text-sm">🚫 Teacher Cancelled</span>
            </label>
        </div>
    </div>
    
    <!-- Completed Details -->
    <div class="completed-section" style="{{ $isNew || (!$isNew && $lesson->status === 'completed') ? '' : 'display:none' }}">
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Topic *</label>
            <input 
                type="text" 
                name="topic" 
                value="{{ $isNew ? '' : $lesson->topic }}" 
                class="w-full px-3 py-2 border border-gray-300 rounded-md"
            >
        </div>
        
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Homework</label>
            <textarea 
                name="homework" 
                rows="2" 
                class="w-full px-3 py-2 border border-gray-300 rounded-md"
            >{{ $isNew ? '' : $lesson->homework }}</textarea>
        </div>
        
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
            <textarea 
                name="comments" 
                rows="2" 
                class="w-full px-3 py-2 border border-gray-300 rounded-md"
            >{{ $isNew ? '' : $lesson->comments }}</textarea>
        </div>
    </div>

    <!-- Student Absent Details -->
    <div class="absent-section" style="{{ !$isNew && $lesson->status === 'student_absent' ? '' : 'display:none' }}">
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
            <textarea 
                name="comments" 
                rows="2" 
                placeholder="Why did the student miss the class?"
                class="w-full px-3 py-2 border border-gray-300 rounded-md"
            >{{ $isNew ? '' : $lesson->comments }}</textarea>
        </div>
    </div>

    <!-- Teacher Cancelled Details -->
    <div class="cancelled-section" style="{{ !$isNew && $lesson->status === 'teacher_cancelled' ? '' : 'display:none' }}">
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason * (required)</label>
            <textarea 
                name="comments" 
                rows="2" 
                placeholder="Why was the class cancelled?"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md"
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
            
            // Hide all sections
            if (completedSection) completedSection.style.display = 'none';
            if (absentSection) absentSection.style.display = 'none';
            if (cancelledSection) cancelledSection.style.display = 'none';
            
            // Show relevant section
            if (e.target.value === 'completed' && completedSection) {
                completedSection.style.display = 'block';
            } else if (e.target.value === 'student_absent' && absentSection) {
                absentSection.style.display = 'block';
            } else if (e.target.value === 'teacher_cancelled' && cancelledSection) {
                cancelledSection.style.display = 'block';
            }
        }
    });
</script>
@endonce