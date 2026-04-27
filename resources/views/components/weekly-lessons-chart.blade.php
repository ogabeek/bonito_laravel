@props([
    'distribution',
    'stats' => null,
    'title' => null,
])

@php
    $weeks = collect($distribution['weeks'] ?? []);
    $rangeStart = $distribution['start'] ?? now()->copy()->subMonthsNoOverflow(5)->startOfMonth();
    $rangeEnd = $distribution['end'] ?? $rangeStart->copy()->addYear()->subDay();
    $total = (int) ($distribution['total'] ?? 0);

    $width = 760;
    $height = 132;
    $left = 32;
    $right = 12;
    $top = 18;
    $bottom = 26;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    $slotWidth = $plotWidth / max(1, $weeks->count());
    $cellRows = 4;
    $cellGap = 2;
    $cellHeight = min(10, ($plotHeight - (($cellRows - 1) * $cellGap)) / $cellRows);
    $cellWidth = max(6, min(10, $slotWidth - 2));
    $baseline = $top + $plotHeight;

    $monthGroups = $weeks
        ->groupBy(fn ($week) => $week['start']->format('Y-m'))
        ->map(function ($monthWeeks) use ($weeks): array {
            $firstWeek = $monthWeeks->first();
            $startIndex = $weeks->search(fn ($week) => $week['week'] === $firstWeek['week']);

            return [
                'key' => $firstWeek['start']->format('Y-m'),
                'label' => $firstWeek['start']->format('M'),
                'year' => (int) $firstWeek['start']->format('Y'),
                'startIndex' => $startIndex,
                'count' => $monthWeeks->count(),
                'weeks' => $monthWeeks->values(),
            ];
        })
        ->values();

    $currentMonth = $monthGroups->firstWhere('key', now()->format('Y-m'));
    $focusX = $currentMonth
        ? $left + ($currentMonth['startIndex'] * $slotWidth) + (($currentMonth['count'] * $slotWidth) / 2)
        : $width / 2;
    $yearMarkers = $monthGroups
        ->filter(fn ($month, $index) => $index === 0 || str_ends_with($month['key'], '-01'))
        ->values();
@endphp

<x-card class="mb-6">
    @if($stats)
        @if($title)
            <h2 class="text-base sm:text-xl font-semibold mb-3">{{ $title }}</h2>
        @endif
        <div class="grid grid-cols-[4.5rem_minmax(0,1fr)] sm:grid-cols-[5.5rem_1.5rem_minmax(0,1fr)] items-center gap-2 sm:gap-3 mb-3 sm:mb-4 pb-3 sm:pb-4 border-b text-center">
            <div class="flex flex-col items-center gap-0.5 sm:gap-1">
                <span class="text-base sm:text-2xl font-bold text-gray-700 sm:text-gray-800">{{ $stats['total'] }}</span>
                <span class="text-[10px] sm:text-sm leading-tight text-gray-400 sm:text-gray-500">Total</span>
            </div>
            <div class="hidden sm:block text-lg font-medium text-gray-300">=</div>
            <div class="grid grid-cols-4 gap-1.5 sm:gap-3 border-l border-gray-200 pl-2 sm:border-l-0 sm:pl-0">
                <div class="flex flex-col items-center gap-0.5 sm:gap-1">
                    <span class="text-base sm:text-xl font-semibold text-gray-700 sm:[color:var(--color-status-completed)]">{{ $stats['completed'] }}</span>
                    <span class="text-[10px] sm:text-sm leading-tight text-gray-400 sm:text-gray-500">Completed</span>
                </div>
                <div class="flex flex-col items-center gap-0.5 sm:gap-1">
                    <span class="text-base sm:text-xl font-semibold text-gray-700 sm:[color:var(--color-status-absent)]">{{ $stats['student_absent'] }}</span>
                    <span class="text-[10px] sm:text-sm leading-tight text-gray-400 sm:text-gray-500">Absent</span>
                </div>
                <div class="flex flex-col items-center gap-0.5 sm:gap-1">
                    <span class="text-base sm:text-xl font-semibold text-gray-700 sm:[color:var(--color-status-student-cancelled)]">{{ $stats['student_cancelled'] }}</span>
                    <span class="text-[10px] sm:text-sm leading-tight text-gray-400 sm:text-gray-500">Student cancelled</span>
                </div>
                <div class="flex flex-col items-center gap-0.5 sm:gap-1">
                    <span class="text-base sm:text-xl font-semibold text-gray-700 sm:[color:var(--color-status-cancelled)]">{{ $stats['teacher_cancelled'] }}</span>
                    <span class="text-[10px] sm:text-sm leading-tight text-gray-400 sm:text-gray-500">Teacher cancelled</span>
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg sm:text-xl font-semibold">Weekly Class Distribution</h2>
            </div>

            <div class="flex shrink-0 items-center gap-3 text-[10px] text-gray-500">
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-green-600"></span>Completed</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-gray-300"></span>Other</span>
            </div>
        </div>
    @endif

    <div
        class="overflow-x-auto"
        data-focus-x="{{ $focusX }}"
        x-data
        x-init="$nextTick(() => {
            const focusX = Number($el.dataset.focusX || 0);
            $el.scrollLeft = Math.max(0, ((focusX / {{ $width }}) * $el.scrollWidth) - ($el.clientWidth / 2));
        })"
    >
        <svg
            viewBox="0 0 {{ $width }} {{ $height }}"
            role="img"
            aria-label="Weekly class distribution from {{ $rangeStart->format('M Y') }} to {{ $rangeEnd->format('M Y') }}"
            class="min-w-[640px] sm:min-w-[720px] w-full h-auto"
        >
            <rect x="0" y="0" width="{{ $width }}" height="{{ $height }}" fill="white" />

            @foreach($monthGroups as $month)
                @if($month['key'] === now()->format('Y-m'))
                    @php
                        $monthStartX = $left + ($month['startIndex'] * $slotWidth);
                        $monthWidth = $month['count'] * $slotWidth;
                    @endphp
                    <rect x="{{ $monthStartX }}" y="{{ $top }}" width="{{ $monthWidth }}" height="{{ $baseline - $top }}" fill="#f7fbf8" />
                @endif
            @endforeach

            @foreach($monthGroups as $month)
                @php
                    $monthCompleted = $month['weeks']->sum('completed');
                    $monthStartX = $left + ($month['startIndex'] * $slotWidth);
                    $monthWidth = $month['count'] * $slotWidth;
                    $monthCenterX = $monthStartX + ($monthWidth / 2);
                @endphp
                @if($monthCompleted > 0)
                    <text
                        x="{{ $monthCenterX }}"
                        y="{{ $top + $plotHeight * 0.55 }}"
                        text-anchor="middle"
                        dominant-baseline="central"
                        font-size="24"
                        font-weight="700"
                        fill="#000"
                        opacity="0.07"
                    >{{ $monthCompleted }}</text>
                @endif
            @endforeach

            @foreach($yearMarkers as $marker)
                @php
                    $markerStartX = $left + ($marker['startIndex'] * $slotWidth);
                    $markerWidth = $marker['count'] * $slotWidth;
                    $markerCenterX = $markerStartX + ($markerWidth / 2);
                @endphp
                <text x="{{ $markerCenterX }}" y="9" text-anchor="middle" font-size="10" font-weight="600" fill="#6b7280">{{ $marker['year'] }}</text>
            @endforeach

            @foreach($monthGroups as $month)
                @php
                    $monthStartX = $left + ($month['startIndex'] * $slotWidth);
                    $monthWidth = $month['count'] * $slotWidth;
                    $monthCenterX = $monthStartX + ($monthWidth / 2);
                @endphp
                @if(!$loop->first)
                    <line x1="{{ $monthStartX }}" y1="{{ $top }}" x2="{{ $monthStartX }}" y2="{{ $baseline }}" stroke="#e9edf1" stroke-width="1" />
                @endif
                <text x="{{ $monthCenterX }}" y="{{ $height - 8 }}" text-anchor="middle" font-size="10" font-weight="600" fill="#6b7280">{{ $month['label'] }}</text>
            @endforeach

            <line x1="{{ $left }}" y1="{{ $top }}" x2="{{ $left }}" y2="{{ $baseline }}" stroke="#d9dee3" stroke-width="1" />
            <line x1="{{ $left }}" y1="{{ $baseline }}" x2="{{ $width - $right }}" y2="{{ $baseline }}" stroke="#d9dee3" stroke-width="1" />

            @foreach($weeks as $week)
                @php
                    $count = (int) $week['count'];
                    $completed = (int) $week['completed'];
                    $other = (int) $week['other'];
                    $visibleCount = min($count, $cellRows);
                    $x = $left + ($loop->index * $slotWidth) + (($slotWidth - $cellWidth) / 2);
                    $start = $week['start']->format('M j');
                    $end = $week['end']->format('M j');
                @endphp
                @if($count > 0)
                    <g>
                        <title>{{ $start }} - {{ $end }}: {{ $count }} {{ Str::plural('class', $count) }} ({{ $completed }} completed, {{ $other }} not completed)</title>
                        @for($cell = 1; $cell <= $visibleCount; $cell++)
                            @php
                                $y = $baseline - ($cell * $cellHeight) - (($cell - 1) * $cellGap);
                                $visibleCompleted = min($completed, $cellRows);
                                $isCompletedCell = $cell <= $visibleCompleted;
                                $cellFill = $isCompletedCell ? '#2f855a' : '#d1d5db';
                                $cellHeightForWeek = $cell === 1 && $count === 1 ? $cellHeight * 1.55 : $cellHeight;
                                $cellY = $cell === 1 && $count === 1 ? $baseline - $cellHeightForWeek : $y;
                            @endphp
                            <rect
                                x="{{ $x }}"
                                y="{{ $cellY }}"
                                width="{{ $cellWidth }}"
                                height="{{ $cellHeightForWeek }}"
                                rx="2"
                                fill="{{ $cellFill }}"
                            />
                        @endfor
                    </g>
                @endif
            @endforeach
        </svg>
    </div>

    <div class="mt-2 text-[10px] text-gray-400 sm:hidden">Scroll sideways for more months</div>

    @if($total === 0)
        <div class="mt-3 text-sm text-gray-500">No lessons recorded in this period.</div>
    @endif
</x-card>
