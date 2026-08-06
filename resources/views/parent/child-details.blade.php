<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->first_name }} {{ $student->last_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-md mx-auto bg-white min-h-screen shadow-lg">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6 rounded-b-3xl shadow-md relative">
            <a href="{{ route('parent.dashboard') }}" class="absolute top-6 left-6 text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div class="text-center mt-4">
                <div class="w-20 h-20 bg-white text-blue-600 rounded-full flex items-center justify-center font-bold text-2xl mx-auto">
                    {{ strtoupper(substr($student->first_name, 0, 1)) }}
                </div>
                <h1 class="text-xl font-bold mt-3">{{ $student->first_name }} {{ $student->last_name }}</h1>
                <p class="text-blue-100 text-sm">{{ $student->currentClass->name ?? 'Classe inconnue' }}</p>
            </div>
        </div>

        <!-- Menu Grid -->
        <!-- Menu Grid -->
        <div class="p-6 grid grid-cols-2 gap-4">
            <a href="{{ route('parent.grades.index', $student->id) }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
                <div class="text-3xl mb-2">📊</div>
                <h3 class="font-semibold text-gray-700">Notes</h3>
            </a>

            <a href="{{ route('parent.attendance.index', $student->id) }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
                <div class="text-3xl mb-2">📅</div>
                <h3 class="font-semibold text-gray-700">Présences</h3>
            </a>

            <a href="{{ route('parent.payments.index', $student->id) }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
                <div class="text-3xl mb-2">💰</div>
                <h3 class="font-semibold text-gray-700">Paiements</h3>
            </a>

            <a href="{{ route('parent.messages.index') }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
                <div class="text-3xl mb-2">📨</div>
                <h3 class="font-semibold text-gray-700">Messages</h3>
            </a>
        </div>
    </div>
</body>

</html>