<div>
    <h1 class="text-2xl font-semibold">Welcome, {{ auth()->user()->name }}</h1>
    <p class="mt-2 text-sm text-zinc-600">You are signed in as {{ auth()->user()->role->label() }}. Sales, customers and reports will appear here as they are built.</p>
</div>
