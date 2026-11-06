@extends('layouts.app')

@section('title', 'Acceptations du règlement')
@section('page_title', 'Règlement intérieur — acceptations')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <a href="{{ route('app.regulations.index') }}" class="text-sm text-gray-500 hover:text-primary">← Règlement intérieur</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $regulation->title }} (v{{ $regulation->version }})</h1>
            <p class="text-gray-600">{{ $acceptedAt->count() }} acceptation(s)</p>
        </div>
        <div class="flex gap-2 text-sm">
            @foreach(['' => 'Tous', 'accepted' => 'Ont accepté', 'pending' => 'En attente'] as $value => $label)
            <a href="{{ route('app.regulations.show', [$regulation, 'status' => $value ?: null]) }}"
               class="px-3 py-1.5 rounded-lg {{ request('status', '') === $value ? 'bg-primary text-white' : 'bg-gray-100 hover:bg-gray-200' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Parent</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parents as $parent)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 font-semibold">{{ $parent->last_name }} {{ $parent->first_name }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $parent->phone ?? '—' }}</td>
                    <td class="py-3 px-4">
                        @if($acceptedAt->has($parent->id))
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Accepté le {{ \Carbon\Carbon::parse($acceptedAt[$parent->id])->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">En attente</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-8 text-center text-gray-500">Aucun parent</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($parents->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">{{ $parents->links() }}</div>
        @endif
    </div>
</div>
@endsection
