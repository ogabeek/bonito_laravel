@extends('layouts.app', ['favicon' => 'favicon-teacher.svg'])

@section('title', 'Teacher Login')

@section('content')
<x-login-card 
    title="Teacher Login" 
    :action="route('teacher.login.submit', $teacher->id)"
>
    <p class="text-gray-600 mb-4">Hello, {{ $teacher->name }}</p>
</x-login-card>

@push('scripts')
<script>
// Ensure CSRF token is fresh before submitting the login form.
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action="{{ route('teacher.login.submit', $teacher->id) }}"]');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        // If token looks stale we fetch a new one before actual submit
        const tokenInput = form.querySelector('input[name="_token"]');
        try {
            const res = await fetch('/csrf-token', {credentials: 'same-origin'});
            if (res.ok) {
                const data = await res.json();
                if (tokenInput) tokenInput.value = data.token;
                // update meta tag (for JS-based fetches)
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.content = data.token;
            }
        } catch (err) {
            // network problem - let regular submit proceed and fallback to server error
            console.error('Could not refresh CSRF token', err);
        }
        // proceed with form submission
    }, {passive: false});
});
</script>
@endpush
@endsection