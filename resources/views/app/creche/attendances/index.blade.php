@extends('layouts.app')

@section('title', 'Pointage Crèche')
@section('page_title', 'Pointage Crèche')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Groupe *</label>
                <select name="class_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>{{ $class->name }} ({{ $class->level }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>
        </form>
    </div>

    @if($classId)
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        @if($students->isEmpty())
        <p class="text-center text-gray-500 py-8">Aucun enfant inscrit dans ce groupe.</p>
        @else
        <form action="{{ route('app.creche-attendances.store') }}" method="POST">
            @csrf
            <input type="hidden" name="class_id" value="{{ $classId }}">
            <input type="hidden" name="date" value="{{ $date }}">

            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Enfant</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Présent</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Absent</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Arrivée</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Départ</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Déposé par</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Récupéré par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($students as $student)
                    @php $existing = $student->crecheAttendances->first(); @endphp
                    <tr>
                        <td class="py-2 px-3">{{ $student->last_name }} {{ $student->first_name }}</td>
                        <td class="py-2 px-3 text-center">
                            <input type="radio" name="records[{{ $student->id }}][status]" value="present" {{ !$existing || $existing->status === 'present' ? 'checked' : '' }} class="w-4 h-4 text-primary">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="radio" name="records[{{ $student->id }}][status]" value="absent" {{ $existing && $existing->status === 'absent' ? 'checked' : '' }} class="w-4 h-4 text-red-600">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="time" name="records[{{ $student->id }}][checked_in_at]" value="{{ $existing?->checked_in_at?->format('H:i') }}" class="px-2 py-1 border border-gray-300 rounded">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="time" name="records[{{ $student->id }}][checked_out_at]" value="{{ $existing?->checked_out_at?->format('H:i') }}" class="px-2 py-1 border border-gray-300 rounded">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="text" name="records[{{ $student->id }}][dropped_off_by]" value="{{ $existing?->dropped_off_by }}" placeholder="Nom" class="px-2 py-1 border border-gray-300 rounded w-32">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="text" name="records[{{ $student->id }}][picked_up_by]" value="{{ $existing?->picked_up_by }}" placeholder="Nom" class="px-2 py-1 border border-gray-300 rounded w-32">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-semibold transition">✅ Enregistrer le pointage</button>
            </div>
        </form>
        @endif
    </div>
    @endif
</div>
@endsection
