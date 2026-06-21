@props(['ledger'])

@php
    $fmt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    $cutoffLabel = \Illuminate\Support\Carbon::parse($ledger['cutoff'])->format('M j, Y');
@endphp

<x-card title="Balance ledger" class="mb-6">
    @if(! $ledger['has_balance_data'])
        <p class="text-sm text-gray-500">
            No payment data yet — add this student to the <span class="font-medium">Clients balance</span> sheet
            (with their UUID) to track a balance.
        </p>
    @else
        @php $negative = $ledger['current_balance'] !== null && $ledger['current_balance'] < 0; @endphp

        {{-- Summary --}}
        <div class="mb-4 grid grid-cols-2 gap-3 text-center sm:grid-cols-4 sm:text-left">
            <div>
                <div class="text-xs text-gray-500">Balance now</div>
                <div class="text-2xl font-bold {{ $negative ? 'text-red-600' : 'text-gray-900' }}">{{ $fmt($ledger['current_balance']) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Paid</div>
                <div class="text-lg font-semibold text-gray-700">{{ $fmt($ledger['paid']) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Used</div>
                <div class="text-lg font-semibold text-gray-700">{{ $ledger['used'] }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Opening ({{ $cutoffLabel }})</div>
                <div class="text-lg font-semibold text-gray-700">{{ $fmt($ledger['opening']) }}</div>
            </div>
        </div>

        @if($ledger['entries']->isEmpty())
            <p class="text-sm text-gray-500">No lessons or payments since {{ $cutoffLabel }}.</p>
        @else
            <div class="-mx-2 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-2 py-1.5 font-medium">Date</th>
                            <th class="px-2 py-1.5 font-medium">Event</th>
                            <th class="px-2 py-1.5 text-right font-medium">Change</th>
                            <th class="px-2 py-1.5 text-right font-medium">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ledger['entries']->reverse() as $e)
                            <tr class="border-b border-gray-100">
                                <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ \Illuminate\Support\Carbon::parse($e['date'])->format('M j, Y') }}</td>
                                <td class="px-2 py-1.5">
                                    @if($e['type'] === 'payment')
                                        <span class="font-medium text-green-700">Payment</span>
                                    @else
                                        <span class="inline-block rounded px-1.5 py-0.5 text-xs {{ $e['status']->badgeClass() }}">{{ $e['label'] }}</span>
                                        @if($e['detail'])<span class="ml-1 text-xs text-gray-400">{{ $e['detail'] }}</span>@endif
                                    @endif
                                </td>
                                <td class="px-2 py-1.5 text-right tabular-nums {{ $e['delta'] > 0 ? 'text-green-700' : ($e['delta'] < 0 ? 'text-gray-500' : 'text-gray-300') }}">
                                    @if($e['delta'] > 0) +{{ $fmt($e['delta']) }}
                                    @elseif($e['delta'] < 0) {{ $fmt($e['delta']) }}
                                    @else · @endif
                                </td>
                                <td class="px-2 py-1.5 text-right font-medium tabular-nums {{ $e['balance'] !== null && $e['balance'] < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $fmt($e['balance']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="text-gray-400">
                            <td class="whitespace-nowrap px-2 py-1.5">{{ $cutoffLabel }}</td>
                            <td class="px-2 py-1.5 italic">Opening balance</td>
                            <td class="px-2 py-1.5 text-right"></td>
                            <td class="px-2 py-1.5 text-right font-medium text-gray-600">{{ $fmt($ledger['opening']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</x-card>
