@props(['selected' => null, 'name' => 'class_date'])

<div x-data="{ 
    date: new Date(),
    selected: '{{ $selected ?? now()->format('Y-m-d') }}',
    today: '{{ now()->format('Y-m-d') }}',
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
    },
    isToday(d) {
        return this.fmt(d) === this.today;
    }
}" class="calendar-container flex-shrink-0">
    <input type="hidden" name="{{ $name }}" x-model="selected" required>
    
    <div class="border rounded" style="padding: var(--spacing-sm);">
        <div class="flex justify-between items-center" style="margin-bottom: var(--spacing-sm);">
            <button type="button" @click="date = new Date(year, month - 1)" style="padding: var(--spacing-xs);" class="hover:bg-gray-100 rounded">←</button>
            <span style="font-weight: var(--font-weight-medium);" x-text="date.toLocaleDateString('en-US', {month:'short', year:'numeric'})"></span>
            <button type="button" @click="date = new Date(year, month + 1)" style="padding: var(--spacing-xs);" class="hover:bg-gray-100 rounded">→</button>
        </div>
        
        <div class="grid grid-cols-7 gap-0.5 text-center">
            <div style="color: var(--color-text-secondary);" class="p-0.5">M</div>
            <div style="color: var(--color-text-secondary);" class="p-0.5">T</div>
            <div style="color: var(--color-text-secondary);" class="p-0.5">W</div>
            <div style="color: var(--color-text-secondary);" class="p-0.5">T</div>
            <div style="color: var(--color-text-secondary);" class="p-0.5">F</div>
            <div style="color: var(--color-text-secondary);" class="p-0.5">S</div>
            <div style="color: var(--color-text-secondary);" class="p-0.5">S</div>
            
            <template x-for="(d, index) in days" :key="index">
                <div class="aspect-square relative">
                    <button 
                        x-show="d > 0"
                        type="button" 
                        @click="selected = fmt(d)"
                        :class="{
                            'ring-1 ring-blue-400': isToday(d) && selected !== fmt(d),
                            'bg-blue-600 text-white font-semibold': selected === fmt(d)
                        }"
                        class="p-0.5 rounded aspect-square hover:bg-gray-100 w-full h-full transition"
                        x-text="d">
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
