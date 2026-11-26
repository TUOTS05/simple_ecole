@extends('layouts.app')

@section('title', 'Personnalisation')
@section('page_title', "Personnalisation de l'école")

@php
    $input = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary';
    $label = 'block text-sm font-medium text-gray-700 mb-2';
    $subjectScale = $s('report_card.subject_scale');
    $generalScale = $s('report_card.general_scale');
    $scaleRows = fn (array $scale) => array_pad(array_values($scale), 8, ['min' => '', 'label' => '']);
@endphp

@section('content')
<div class="max-w-5xl mx-auto"
     x-data="{
        tab: @js(request('onglet', $errors->has('matricule_pattern') || $errors->has('admission_pattern') ? 'numbers' : 'identity')),
        color: @js($s('identity.primary_color')),
        mPattern: @js($s('matricule.pattern')), mCode: @js($s('matricule.code') ?? ''), mDigits: @js((int) $s('matricule.digits')), mStart: @js((int) $s('matricule.start')),
        aPattern: @js($s('admission.pattern')), aDigits: @js((int) $s('admission.digits')), aStart: @js((int) $s('admission.start')),
        defaultCode: @js($defaultCode),
        preview(pattern, digits, start) {
            const year = String(new Date().getFullYear());
            const code = (this.mCode || this.defaultCode).toUpperCase();
            if (!pattern.includes('{NUM}')) pattern += '{NUM}';
            return pattern.replaceAll('{ANNEE}', year).replaceAll('{AA}', year.slice(-2))
                .replaceAll('{CODE}', code).replaceAll('{NUM}', String(start || 1).padStart(Number(digits) || 1, '0'));
        },
     }">

    @if(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <ul class="list-disc list-inside text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <p class="text-gray-600 mb-4">
        Adaptez Simple Ecole à votre établissement.
        @if(auth()->user()->isSchoolAdmin())
        Le nom, l'adresse, le téléphone et le logo se modifient dans
        <a href="{{ route('app.profile.edit') }}" class="text-primary underline">Mon profil</a>.
        @endif
    </p>

    {{-- Onglets --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach(['identity' => '🎨 Espace & documents', 'numbers' => '🔢 Matricules', 'report' => '📄 Bulletins', 'subjects' => '📚 Matières'] as $key => $title)
            <button type="button" @click="tab = @js($key)"
                    class="px-4 py-2 rounded-lg font-medium transition"
                    :class="tab === @js($key) ? 'bg-primary text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'">
                {{ $title }}
            </button>
        @endforeach
    </div>

    <form action="{{ route('app.settings.customization.update') }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        {{-- ============ ESPACE & DOCUMENTS ============ --}}
        <div x-show="tab === 'identity'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="{{ $label }}">Couleur principale de votre espace</label>
                    <div class="flex items-center gap-3">
                        <input type="color" x-model="color" class="h-10 w-16 border border-gray-300 rounded cursor-pointer">
                        <input type="text" name="identity_primary_color" x-model="color" maxlength="7" class="{{ $input }} font-mono">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Boutons, menu actif et en-têtes de votre espace (et de celui de votre personnel).</p>
                </div>
                <div>
                    <label class="{{ $label }}">Sigle / nom court</label>
                    <input type="text" name="identity_short_name" value="{{ $s('identity.short_name') }}" maxlength="30" placeholder="{{ $school->name }}" class="{{ $input }}">
                    <p class="text-xs text-gray-500 mt-1">Affiché en haut du menu à la place du nom complet.</p>
                </div>
                <div>
                    <label class="{{ $label }}">Ligne d'en-tête 1 des documents</label>
                    <input type="text" name="identity_header_line1" value="{{ $s('identity.header_line1') }}" maxlength="120" placeholder="Ex. République de Côte d'Ivoire" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Ligne d'en-tête 2 des documents</label>
                    <input type="text" name="identity_header_line2" value="{{ $s('identity.header_line2') }}" maxlength="120" placeholder="Ex. Ministère de l'Éducation Nationale" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Devise de l'école</label>
                    <input type="text" name="identity_motto" value="{{ $s('identity.motto') }}" maxlength="150" placeholder="Ex. Travail – Discipline – Réussite" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Ville (« Fait à … » sur les documents)</label>
                    <input type="text" name="identity_city" value="{{ $s('identity.city') }}" maxlength="80" placeholder="Abidjan" class="{{ $input }}">
                </div>
            </div>
        </div>

        {{-- ============ MATRICULES ============ --}}
        <div x-show="tab === 'numbers'" x-cloak class="space-y-8">
            <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                Balises disponibles :
                @foreach($tokens as $token => $meaning)
                    <code class="bg-white border border-gray-200 rounded px-1">{{ $token }}</code> {{ $meaning }}@if(! $loop->last) · @endif
                @endforeach
                <br>Avec {ANNEE} ou {AA}, la numérotation repart automatiquement chaque année. Les numéros déjà attribués ne changent pas.
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Matricule de l'élève</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Format</label>
                        <input type="text" name="matricule_pattern" x-model="mPattern" maxlength="40" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label class="{{ $label }}">Code école</label>
                        <input type="text" name="matricule_code" x-model="mCode" maxlength="6" :placeholder="defaultCode" class="{{ $input }} font-mono uppercase">
                    </div>
                    <div>
                        <label class="{{ $label }}">Chiffres du numéro</label>
                        <input type="number" name="matricule_digits" x-model="mDigits" min="1" max="8" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Commencer à</label>
                        <input type="number" name="matricule_start" x-model="mStart" min="1" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-3 flex items-end">
                        <p class="text-sm text-gray-700">
                            Aperçu : <strong class="font-mono" x-text="preview(mPattern, mDigits, mStart)"></strong>
                            <span class="text-gray-500 ml-2">(prochain matricule réel : <span class="font-mono">{{ $nextMatricule }}</span>)</span>
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Numéro d'admission</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Format</label>
                        <input type="text" name="admission_pattern" x-model="aPattern" maxlength="40" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label class="{{ $label }}">Chiffres du numéro</label>
                        <input type="number" name="admission_digits" x-model="aDigits" min="1" max="8" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Commencer à</label>
                        <input type="number" name="admission_start" x-model="aStart" min="1" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-4">
                        <p class="text-sm text-gray-700">
                            Aperçu : <strong class="font-mono" x-text="preview(aPattern, aDigits, aStart)"></strong>
                            <span class="text-gray-500 ml-2">(prochain numéro réel : <span class="font-mono">{{ $nextAdmission }}</span>)</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ BULLETINS ============ --}}
        <div x-show="tab === 'report'" x-cloak class="space-y-8">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Éléments affichés</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach([
                        'show_rank' => 'Classement (rang)',
                        'show_class_size' => 'Effectif de la classe',
                        'show_coefficient' => 'Colonne des coefficients',
                        'show_mention' => 'Mention générale',
                        'group_by_domain' => 'Regrouper les matières par domaine',
                    ] as $key => $text)
                        <label class="flex items-center gap-2 text-gray-700">
                            <input type="hidden" name="report_card_{{ $key }}" value="0">
                            <input type="checkbox" name="report_card_{{ $key }}" value="1" @checked($s('report_card.'.$key)) class="form-checkbox rounded text-primary">
                            {{ $text }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach(['subject_scale' => ['Appréciation par matière', $subjectScale], 'general_scale' => ['Mention générale', $generalScale]] as $key => [$title, $scale])
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $title }}</h3>
                        <p class="text-xs text-gray-500 mb-3">Note sur 20 à partir de laquelle le libellé s'applique. Laissez une ligne vide pour la retirer.</p>
                        <div class="space-y-2">
                            @foreach($scaleRows($scale) as $i => $row)
                                <div class="flex gap-2">
                                    <input type="number" step="0.25" min="0" max="20" name="report_card_{{ $key }}[{{ $i }}][min]" value="{{ $row['min'] }}" placeholder="Seuil" class="w-24 px-3 py-2 border border-gray-300 rounded-lg">
                                    <input type="text" name="report_card_{{ $key }}[{{ $i }}][label]" value="{{ $row['label'] }}" maxlength="40" placeholder="Libellé" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="{{ $label }}">Titre de la mention générale</label>
                    <input type="text" name="report_card_mention_title" value="{{ $s('report_card.mention_title') }}" maxlength="60" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Nom du directeur / de la directrice (sous la signature)</label>
                    <input type="text" name="report_card_director_name" value="{{ $s('report_card.director_name') }}" maxlength="100" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Titre du cadre de gauche</label>
                    <input type="text" name="report_card_left_box_title" value="{{ $s('report_card.left_box_title') }}" maxlength="80" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Titre du cadre de droite</label>
                    <input type="text" name="report_card_right_box_title" value="{{ $s('report_card.right_box_title') }}" maxlength="80" class="{{ $input }}">
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}">Note en bas du bulletin</label>
                    <input type="text" name="report_card_footer_note" value="{{ $s('report_card.footer_note') }}" maxlength="200" placeholder="Ex. Bulletin à conserver précieusement. Aucun duplicata ne sera délivré." class="{{ $input }}">
                </div>
            </div>
        </div>

        {{-- ============ MATIÈRES ============ --}}
        <div x-show="tab === 'subjects'" x-cloak class="space-y-6">
            <p class="text-sm text-gray-600">
                Liste proposée automatiquement quand vous configurez les matières d'un niveau (une matière par ligne, dans l'ordre d'affichage).
                Pour l'ordre, les domaines et la copie d'une année ou d'un niveau à l'autre, utilisez la page
                <a href="{{ route('app.subjects.index') }}" class="text-primary underline">Matières</a>.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach(['maternelle' => '🧒 Maternelle', 'primaire' => '📚 Primaire'] as $cycle => $title)
                    <div>
                        <label class="{{ $label }}">{{ $title }}</label>
                        <textarea name="subjects_{{ $cycle }}" rows="16" class="{{ $input }} font-mono text-sm">{{ is_array($s('subjects.'.$cycle)) ? implode("\n", $s('subjects.'.$cycle)) : $s('subjects.'.$cycle) }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-semibold transition">Enregistrer la personnalisation</button>
        </div>
    </form>
</div>
@endsection
