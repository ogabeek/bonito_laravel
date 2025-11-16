@props(['lesson'])

<div>
    <div class="flex justify-between items-start gap-4">
        <div class="flex-shrink-0">
            <div class="mb-2">
                <div class="text-sm font-bold text-gray-900">
                    {{ $lesson->class_date->format('D') }} {{ $lesson->class_date->format('d') }}
                </div>
                <div class="text-xs font-medium text-gray-700">
                    {{ $lesson->student->name }}
                </div>
            </div>
            <button @click="editing = true" class="text-xs text-blue-600 hover:text-blue-800 hover:underline">Edit</button>
        </div>
        
        <div class="flex-1">
            @if($lesson->status === 'completed')
                <div class="text-xs text-gray-600 space-y-0.5 flex justify-end">
                    <div class="space-y-0.5">
                        <div><span class="font-semibold">Topic:</span> {{ $lesson->topic }}</div>
                        @if($lesson->homework)
                            <div><span class="font-semibold">Homework:</span> {{ $lesson->homework }}</div>
                        @endif
                        @if($lesson->comments)
                            <div><span class="font-semibold">Notes:</span> {{ $lesson->comments }}</div>
                        @endif
                    </div>
                </div>
            @elseif($lesson->status === 'student_absent')
                <div class="text-xs text-gray-600 flex justify-end">
                    @if($lesson->comments)
                        <div><span class="font-semibold">Notes:</span> {{ $lesson->comments }}</div>
                    @else
                        <span class="text-gray-400 italic">Student did not attend</span>
                    @endif
                </div>
            @elseif($lesson->status === 'teacher_cancelled')
                <div class="text-xs text-gray-600 flex justify-end">
                    @if($lesson->comments)
                        <div><span class="font-semibold">Reason:</span> {{ $lesson->comments }}</div>
                    @else
                        <span class="text-gray-400 italic">Cancelled by teacher</span>
                    @endif
                </div>
            @endif
        </div>
        
        <div class="flex-shrink-0">
            <x-status-badge :status="$lesson->status" />
        </div>
    </div>
</div>
