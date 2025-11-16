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
    @endif
    
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select 
            name="status" 
            class="status-select w-full px-3 py-2 border border-gray-300 rounded-md" 
            {{ $isNew ? 'id="newLessonStatus"' : '' }}
        >
            <option value="scheduled" {{ !$isNew && $lesson->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            <option value="completed" {{ !$isNew && $lesson->status === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="student_absent" {{ !$isNew && $lesson->status === 'student_absent' ? 'selected' : '' }}>Student Absent</option>
            <option value="teacher_cancelled" {{ !$isNew && $lesson->status === 'teacher_cancelled' ? 'selected' : '' }}>Teacher Cancelled</option>
        </select>
    </div>
    
<div class="details-section" style="{{ ($isNew ? '' : ($lesson->status === 'completed' ? '' : 'display:none')) }}">        <div class="mb-3">
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
</div>

@once
<script>
    // Show/hide details based on status selection
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('status-select')) {
            const form = e.target.closest('form');
            const detailsSection = form.querySelector('.details-section');
            if (detailsSection) {
                detailsSection.style.display = e.target.value === 'completed' ? 'block' : 'none';
            }
        }
    });
</script>
@endonce