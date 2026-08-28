@extends('layouts.app')

@section('title', 'Modifier '.$extra->name)
@section('page_title', $extra->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

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

    <!-- Fiche extra -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Fiche de l'extra</h3>
        <form action="{{ route('extras.catalogue.update', $extra->id) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie *</label>
                    <select name="extra_category_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $extra->extra_category_id == $category->id ? 'selected' : '' }}>{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                    <input type="text" name="code" required maxlength="30" value="{{ $extra->code }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du service *</label>
                <input type="text" name="name" required maxlength="150" value="{{ $extra->name }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" maxlength="1000" class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ $extra->description }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="active" {{ $extra->status === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="inactive" {{ $extra->status === 'inactive' ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de facturation *</label>
                    <select name="billing_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="recurring" {{ $extra->billing_type === 'recurring' ? 'selected' : '' }}>Périodique</option>
                        <option value="one_time" {{ $extra->billing_type === 'one_time' ? 'selected' : '' }}>Frais unique</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacité max.</label>
                    <input type="number" name="capacity" min="1" value="{{ $extra->capacity }}" placeholder="Illimitée" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Public concerné</label>
                    <input type="text" name="target_audience" maxlength="100" value="{{ $extra->target_audience }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" name="start_date" value="{{ $extra->start_date?->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" name="end_date" value="{{ $extra->end_date?->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Conditions d'accès</label>
                <textarea name="conditions" rows="2" maxlength="1000" class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ $extra->conditions }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-gray-100 pt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Salle / lieu</label>
                    <input type="text" name="location" maxlength="150" value="{{ $extra->location }}" placeholder="Salle 3, Cour..." class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fermeture (garderie)</label>
                    <input type="time" name="daycare_closing_time" value="{{ $extra->daycare_closing_time }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tarif dépassement (FCFA/min)</label>
                    <input type="number" name="overage_rate_per_minute" min="0" step="0.01" value="{{ $extra->overage_rate_per_minute }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destination (sortie scolaire)</label>
                    <input type="text" name="destination" maxlength="150" value="{{ $extra->destination }}" placeholder="Parc national du Banco..." class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date limite d'inscription</label>
                    <input type="date" name="registration_deadline" value="{{ $extra->registration_deadline?->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="includes_transport" value="1" id="includes_transport" {{ $extra->includes_transport ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 rounded">
                    <label for="includes_transport" class="text-sm text-gray-700">Le transport est inclus</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="requires_parental_authorization" value="1" id="requires_parental_authorization" {{ $extra->requires_parental_authorization ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 rounded">
                    <label for="requires_parental_authorization" class="text-sm text-gray-700">Autorisation parentale requise</label>
                </div>
            </div>

            <div class="flex justify-between items-center pt-4">
                <form action="{{ route('extras.catalogue.destroy', $extra->id) }}" method="POST" onsubmit="return confirm('Supprimer définitivement cet extra ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-sm">🗑️ Supprimer l'extra</button>
                </form>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Enregistrer</button>
            </div>
        </form>
    </div>

    <!-- Tarifs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">💰 Tarifs</h3>

        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Année</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Classe</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600">Montant</th>
                        @if($extra->isRecurring())
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Périodes</th>
                        @endif
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tarifs as $tarif)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3">{{ $tarif->schoolYear->name }}</td>
                        <td class="py-2 px-3">{{ $tarif->schoolClass->name ?? 'Toutes les classes' }}</td>
                        <td class="py-2 px-3 text-right font-semibold text-primary">{{ number_format($tarif->amount, 0, ',', ' ') }} FCFA</td>
                        @if($extra->isRecurring())
                        <td class="py-2 px-3 text-center text-gray-500">
                            @if($tarif->is_open_ended)
                            <span class="text-primary font-semibold">🔁 Mensuel continu</span>
                            @else
                            {{ $tarif->start_period }} → {{ $tarif->end_period }} ({{ $tarif->periods_count }} périodes)
                            @endif
                        </td>
                        @endif
                        <td class="py-2 px-3 text-center">
                            <form action="{{ route('extras.tarifs.destroy', $tarif->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce tarif ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-500">Aucun tarif défini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-700 mb-3">+ Ajouter un tarif</h4>
        <form action="{{ route('extras.tarifs.store', $extra->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4" x-data="{ openEnded: false }">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année scolaire *</label>
                <select name="school_year_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" {{ $year->is_active ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Classe (vide = toutes)</label>
                <select name="school_class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Montant (FCFA) *</label>
                <input type="number" name="amount" required min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            @if($extra->isRecurring())
            <div class="md:col-span-3 flex items-center gap-2">
                <input type="checkbox" name="is_open_ended" value="1" x-model="openEnded" id="is_open_ended" class="w-4 h-4 text-primary border-gray-300 rounded">
                <label for="is_open_ended" class="text-sm text-gray-700">🔁 Facturation mensuelle continue (sans date de fin — une échéance est générée chaque mois tant que l'abonnement reste actif)</label>
            </div>
            <div x-show="!openEnded">
                <label class="block text-sm font-medium text-gray-700 mb-1">Début de période (AAAA-MM)</label>
                <input type="month" name="start_period" :disabled="openEnded" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div x-show="!openEnded">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fin de période (AAAA-MM)</label>
                <input type="month" name="end_period" :disabled="openEnded" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jour d'échéance</label>
                <input type="number" name="due_day" min="1" max="28" value="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            @endif

            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" maxlength="500" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Ajouter le tarif</button>
            </div>
        </form>
    </div>

    <!-- Planning (jours/horaires) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">🗓️ Planning</h3>

        <div class="space-y-2 mb-6">
            @forelse($schedules as $schedule)
            <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-2 text-sm">
                <span>{{ $schedule->day_label }} — {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} à {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</span>
                <form action="{{ route('extras.schedules.destroy', $schedule->id) }}" method="POST" onsubmit="return confirm('Supprimer ce créneau ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-500">Aucun créneau défini.</p>
            @endforelse
        </div>

        <form action="{{ route('extras.schedules.store', $extra->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <select name="day_of_week" required class="px-3 py-2 border border-gray-300 rounded-lg bg-white">
                <option value="1">Lundi</option>
                <option value="2">Mardi</option>
                <option value="3">Mercredi</option>
                <option value="4">Jeudi</option>
                <option value="5">Vendredi</option>
                <option value="6">Samedi</option>
                <option value="0">Dimanche</option>
            </select>
            <input type="time" name="start_time" required class="px-3 py-2 border border-gray-300 rounded-lg">
            <input type="time" name="end_time" required class="px-3 py-2 border border-gray-300 rounded-lg">
            <button type="submit" class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-semibold transition">+ Ajouter</button>
        </form>
    </div>
</div>
@endsection
