<div class="space-y-3">
    @if($isNew)
        <div class="flex gap-3">
            <!-- Calendar on Left -->
            <div x-data="{ 
                date: new Date(),
                selected: '{{ now()->format('Y-m-d') }}',
                get month() { return this.date.getMonth() },
                get year() { return this.date.getFullYear() },
                get days() {
                    let first = new Date(this.year, this.month, 1).getDay();
                    // Convert Sunday (0) to 7, then subtract 1 to make Monday = 0
                    first = (first === 0 ? 6 : first - 1);
                    let last = new Date(this.year, this.month + 1, 0).getDate();
                    let arr = Array(first).fill(0).concat([...Array(last)].map((_, i) => i + 1));
                    return arr;
                },
                fmt(d) {
                    let m = String(this.month + 1).padStart(2, '0');
                    let day = String(d).padStart(2, '0');
                    return `${this.year}-${m}-${day}`;
                }
            }" class="calendar-container flex-shrink-0">
                <input type="hidden" name="class_date" x-model="selected" required>
                
                <div class="border rounded" style="padding: var(--spacing-sm);">
                    <div class="flex justify-between items-center" style="margin-bottom: var(--spacing-xs);">
                        <button type="button" @click="date = new Date(year, month - 1)" class="px-1 hover:bg-gray-100">←</button>
                        <span style="font-weight: var(--font-weight-medium);" x-text="date.toLocaleDateString('en-US', {month:'short', year:'numeric'})"></span>
                        <button type="button" @click="date = new Date(year, month + 1)" class="px-1 hover:bg-gray-100">→</button>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-0.5 text-center">
                        <div style="color: var(--color-text-secondary);" class="p-0.5">M</div><div style="color: var(--color-text-secondary);" class="p-0.5">T</div><div style="color: var(--color-text-secondary);" class="p-0.5">W</div><div style="color: var(--color-text-secondary);" class="p-0.5">T</div><div style="color: var(--color-text-secondary);" class="p-0.5">F</div><div style="color: var(--color-text-secondary);" class="p-0.5">S</div><div style="color: var(--color-text-secondary);" class="p-0.5">S</div>
                        
                        <template x-for="d in days">
                            <button type="button" 
                                x-show="d > 0"
                                @click="selected = fmt(d)"
                                :style="selected === fmt(d) ? 'background-color: var(--color-primary); color: white; font-weight: var(--font-weight-bold);' : ''"
                                class="p-0.5 rounded aspect-square hover:bg-gray-100"
                                x-text="d">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Side: Student, Status, and Details -->
            <div class="flex-1 space-y-2">
                <div>
                    <div class="flex gap-2 items-end mb-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Student</label>
                            <select name="student_id" required class="w-full px-2 py-1.5 text-sm border rounded">
                                <option value="">Select...</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-1">
                            <label class="status-btn-completed flex items-center gap-1 border rounded cursor-pointer transition" style="padding: var(--spacing-sm); font-size: var(--font-size-sm); font-weight: var(--font-weight-medium);" title="Lesson completed successfully">
                                <input type="radio" name="status" value="completed" class="status-radio hidden" {{ ($isNew || (!$isNew && $lesson->status === 'completed')) ? 'checked' : '' }}>
                                <span>✓ Done</span>
                            </label>
                            
                            <label class="status-btn-absent flex items-center gap-1 border rounded cursor-pointer transition" style="padding: var(--spacing-sm); font-size: var(--font-size-sm); font-weight: var(--font-weight-medium);" title="Student was absent">
                                <input type="radio" name="status" value="student_absent" class="status-radio hidden" {{ (!$isNew && $lesson->status === 'student_absent') ? 'checked' : '' }}>
                                <span>⚠ SA</span>
                            </label>
                            
                            <label class="status-btn-cancelled flex items-center gap-1 border rounded cursor-pointer transition" style="padding: var(--spacing-sm); font-size: var(--font-size-sm); font-weight: var(--font-weight-medium);" title="Cancelled by teacher">
                                <input type="radio" name="status" value="teacher_cancelled" class="status-radio hidden" {{ (!$isNew && $lesson->status === 'teacher_cancelled') ? 'checked' : '' }}>
                                <span>🚫 CT</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Completed Details -->
                <div class="completed-section space-y-2" style="{{ $isNew || (!$isNew && $lesson->status === 'completed') ? '' : 'display:none' }}" x-data="{ showNotes: {{ $isNew ? 'false' : ($lesson->comments ? 'true' : 'false') }} }">
                    <div>
                        <label class="form-label">Topic *</label>
                        <input 
                            type="text" 
                            name="topic" 
                            value="{{ $isNew ? '' : $lesson->topic }}" 
                            {{ ($isNew || (!$isNew && $lesson->status === 'completed')) ? 'required' : '' }}
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
                        >{{ $isNew ? '' : $lesson->homework }}</textarea>
                    </div>
                    
                    <div x-show="!showNotes">
                        <button type="button" @click="showNotes = true" style="font-size: var(--font-size-xs); color: var(--color-primary);" class="hover:underline">+ Add Notes</button>
                    </div>
                    
                    <div x-show="showNotes">
                        <label class="form-label">Notes</label>
                        <textarea 
                            name="comments" 
                            rows="2" 
                            placeholder="Optional"
                            class="form-input w-full"
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
        </div>
    @else
        <div class="text-xs text-gray-600 mb-2 bg-gray-50 px-2 py-1.5 rounded">
            <strong>{{ $lesson->class_date->format('M d, Y') }}</strong> · {{ $lesson->student->name }}
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
            <div class="grid grid-cols-3 gap-2">
                <label class="flex items-center justify-center px-3 py-2 border rounded cursor-pointer transition hover:bg-gray-50 has-[:checked]:bg-green-50 has-[:checked]:border-green-500 has-[:checked]:text-green-700">
                    <input type="radio" name="status" value="completed" class="status-radio hidden" {{ $lesson->status === 'completed' ? 'checked' : '' }}>
                    <span class="text-sm font-medium">✓ Done</span>
                </label>
                <label class="flex items-center justify-center px-3 py-2 border rounded cursor-pointer transition hover:bg-gray-50 has-[:checked]:bg-red-50 has-[:checked]:border-red-500 has-[:checked]:text-red-700">
                    <input type="radio" name="status" value="student_absent" class="status-radio hidden" {{ $lesson->status === 'student_absent' ? 'checked' : '' }}>
                    <span class="text-sm font-medium">⚠ Absent</span>
                </label>
                <label class="flex items-center justify-center px-3 py-2 border rounded cursor-pointer transition hover:bg-gray-50 has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500 has-[:checked]:text-orange-700">
                    <input type="radio" name="status" value="teacher_cancelled" class="status-radio hidden" {{ $lesson->status === 'teacher_cancelled' ? 'checked' : '' }}>
                    <span class="text-sm font-medium">🚫 Cancel</span>
                </label>
            </div>
        </div>

        <div class="completed-section space-y-2" style="{{ $lesson->status === 'completed' ? '' : 'display:none' }}" x-data="{ showNotes: {{ $lesson->comments ? 'true' : 'false' }} }">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Topic *</label>
                <input type="text" name="topic" value="{{ $lesson->topic }}" {{ $lesson->status === 'completed' ? 'required' : '' }} placeholder="What was taught?" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Homework</label>
                <textarea name="homework" rows="2" placeholder="Optional" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">{{ $lesson->homework }}</textarea>
            </div>
            <div x-show="!showNotes">
                <button type="button" @click="showNotes = true" class="text-xs text-blue-600 hover:text-blue-800">+ Add Notes</button>
            </div>
            <div x-show="showNotes">
                <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="comments" rows="2" placeholder="Optional" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">{{ $lesson->comments }}</textarea>
            </div>
        </div>

        <div class="absent-section" style="{{ $lesson->status === 'student_absent' ? '' : 'display:none' }}">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea name="comments" rows="2" placeholder="Why did the student miss?" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">{{ $lesson->comments }}</textarea>
            </div>
        </div>

        <div class="cancelled-section" style="{{ $lesson->status === 'teacher_cancelled' ? '' : 'display:none' }}">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Reason *</label>
                <textarea name="comments" rows="2" placeholder="Why was it cancelled?" {{ $lesson->status === 'teacher_cancelled' ? 'required' : '' }} class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">{{ $lesson->comments }}</textarea>
            </div>
        </div>
    @endif
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