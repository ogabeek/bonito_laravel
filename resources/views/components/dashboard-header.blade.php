@props(['user', 'role' => 'teacher'])

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">{{ $user->name }}'s Dashboard</h1>
    <form method="POST" action="{{ route('teacher.logout') }}">
        @csrf
        <button class="text-gray-600 hover:text-gray-800">Logout</button>
    </form>
</div>
