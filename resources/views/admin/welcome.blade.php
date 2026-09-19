@extends('layouts.staff.base')

@section('content')
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 text-center">
        <div class="user-icon-container">
            <img src="https://www.w3schools.com/howto/img_avatar.png" alt="{{ __('ui.user_icon') }}" class="user-icon">
        </div>
        <style>
            .user-icon-container {
                margin-bottom: 20px;
            }

            .user-icon {
                width: 200px;
                height: 200px;
                border-radius: 50%;
                border: 3px solid #09210a;
                object-fit: cover;
            }
        </style>
        <h1 class="text-4xl font-bold text-gray-800 mb-4">{{ __('ui.welcome_user', ['name' => Auth::guard('staff')->user()->name]) }} 👋</h1>
        <p class="text-lg text-gray-600 mb-6">{{ __('ui.productive_day') }}</p>

        <div class="bg-white shadow-md rounded-xl p-6 w-80">
            <div id="clock" class="text-3xl font-mono text-blue-600"></div>
            <div id="date" class="mt-2 text-gray-500 text-md"></div>
        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            const time = now.toLocaleTimeString();
            const date = now.toLocaleDateString(undefined, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('clock').textContent = time;
            document.getElementById('date').textContent = date;
        }

        setInterval(updateTime, 1000);
        updateTime();
    </script>
@endsection
