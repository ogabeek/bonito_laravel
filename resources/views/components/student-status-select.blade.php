@props(['selected' => null, 'name' => 'status', 'required' => false])

<select
    name="{{ $name }}"
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'px-3 py-2 border rounded']) }}
>
    @foreach(\App\Enums\StudentStatus::cases() as $status)
        <option
            value="{{ $status->value }}"
            {{ $selected === $status ? 'selected' : '' }}
        >
            {{ $status->label() }}
        </option>
    @endforeach
</select>
