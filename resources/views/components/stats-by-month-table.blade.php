@props(['title' => null, 'entities', 'statsByEntity', 'months', 'year' => null])

@php
    $emptyStats = [
        'completed' => 0,
        'student_cancelled' => 0,
        'teacher_cancelled' => 0,
        'student_absent' => 0,
    ];
@endphp

<x-card :title="$title" class="mt-6 overflow-x-auto">
    <table class="min-w-full text-[11px]">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-2 py-2 text-left whitespace-nowrap">{{ $title ? 'Name' : 'Name' }}</th>
                @foreach($months as $month)
                    @php
                        $label = \Carbon\Carbon::createFromDate($year ?? now()->year, $month, 1)->format('M');
                    @endphp
                    <th class="px-2 py-2 text-center whitespace-nowrap">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($entities as $entity)
                <tr class="border-t">
                    <td class="px-2 py-2 whitespace-nowrap">{{ $entity->name }}</td>
                    @foreach($months as $month)
                        @php
                            $stats = $statsByEntity[$entity->id][$month] ?? $emptyStats;
                        @endphp
                        <td class="px-1 py-1 text-right align-top">
                            <x-stats-inline :stats="$stats" class="w-14 ml-auto text-gray-500" />
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</x-card>
