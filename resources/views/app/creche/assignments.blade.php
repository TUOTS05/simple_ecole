@extends('layouts.app')

@section('title', 'Affectations crèche')
@section('page_title', 'Affectations du personnel de crèche')

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

    <div class="bg-blue-50 border border-blue-100 text-blue-800 text-sm rounded-lg px-4 py-3">
        Une auxiliaire ne voit que les enfants des groupes auxquels elle est affectée. L'accueil voit tous les groupes.
        Le directeur et le responsable crèche voient tous les groupes.
    </div>

    @if($groups->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 text-center text-gray-500">
        Aucun groupe de crèche. Créez-en un dans Paramètres › Classes (cycle « Crèche »).
    </div>
    @else

    @if($candidates->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-lg px-4 py-3">
        Aucun membre du personnel n'a de rôle crèche. Créez d'abord un « Auxiliaire de crèche », un « Responsable crèche » ou « Accueil » dans Personnel.
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Nouvelle affectation</h2>
        <form action="{{ route('app.creche.assignments.store') }}" method="POST" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Groupe *</label>
                <select name="school_class_id" required class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ (string) old('school_class_id') === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Personne *</label>
                <select name="user_id" required class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    @foreach($candidates as $person)
                    <option value="{{ $person->id }}" {{ (string) old('user_id') === (string) $person->id ? 'selected' : '' }}>{{ $person->full_name }} ({{ $person->role_label }})</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 py-2">
                <input type="checkbox" name="is_referent" value="1" class="rounded border-gray-300 text-primary focus:ring-primary">
                Référente du groupe
            </label>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-5 py-2 rounded-lg font-semibold transition">Affecter</button>
        </form>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($groups as $group)
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $group->name }}</h3>
                    <p class="text-xs text-gray-500">
                        {{ $group->level }}
                        @if($group->ageRangeLabel()) · {{ $group->ageRangeLabel() }} @endif
                        @if($group->room) · {{ $group->room }} @endif
                    </p>
                </div>
                <span class="text-sm font-semibold {{ $group->students_count >= $group->capacity ? 'text-red-600' : 'text-gray-700' }}">{{ $group->students_count }}/{{ $group->capacity }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                {{ $group->effectiveOpeningTime() }} – {{ $group->effectiveClosingTime() }}
                @if($group->responsible) · Responsable : {{ $group->responsible->full_name }} @endif
            </p>

            <ul class="mt-4 space-y-2">
                @forelse($group->careStaffAssignments as $assignment)
                <li class="flex items-center justify-between text-sm">
                    <span>
                        {{ $assignment->user?->full_name }}
                        <span class="text-gray-500">({{ $assignment->user?->role_label }})</span>
                        @if($assignment->is_referent)<span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-pink-100 text-pink-800">Référente</span>@endif
                    </span>
                    <form action="{{ route('app.creche.assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Retirer cette personne du groupe ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline text-xs">Retirer</button>
                    </form>
                </li>
                @empty
                <li class="text-sm text-gray-400">Personne n'est affecté à ce groupe.</li>
                @endforelse
            </ul>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
