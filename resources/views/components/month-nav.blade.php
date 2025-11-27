@props([
    'currentMonth',
    'prevMonth',
    'nextMonth',
    'routeName',
    'routeParams' => [],
    'showToday' => true,
])

<div class="flex items-center gap-2">
    <a href="{{ route($routeName, array_merge($routeParams, ['year' => $prevMonth->year, 'month' => $prevMonth->month])) }}" 
       class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium transition"
       title="Previous month">
        ← {{ $prevMonth->format('M') }}
    </a>
    
    @if($showToday && !$currentMonth->isCurrentMonth())
        <a href="{{ route($routeName, $routeParams) }}" 
           class="px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-sm font-medium transition"
           title="Go to current month">
            Today
        </a>
    @endif
    
    <a href="{{ route($routeName, array_merge($routeParams, ['year' => $nextMonth->year, 'month' => $nextMonth->month])) }}" 
       class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium transition"
       title="Next month">
        {{ $nextMonth->format('M') }} →
    </a>
</div>
