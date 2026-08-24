{{-- Bloc d'erreurs de validation, identique dans students/create et enrollments/create --}}
@if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 p-4">
        <ul class="list-disc list-inside text-red-700 text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
