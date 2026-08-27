<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SmsSettingsController extends Controller
{
    public function index()
    {
        $schoolId = session('current_school_id');
        $school = School::findOrFail($schoolId);

        // Déchiffrer les clés pour l'affichage
        $clientId = ! empty($school->orange_sms_client_id) ? Crypt::decryptString($school->orange_sms_client_id) : '';
        $clientSecret = ! empty($school->orange_sms_client_secret) ? Crypt::decryptString($school->orange_sms_client_secret) : '';

        // Historique des SMS
        $recentSms = SmsLog::where('school_id', $schoolId)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('app.settings.sms', compact('school', 'clientId', 'clientSecret', 'recentSms'));
    }

    public function update(Request $request)
    {
        $schoolId = session('current_school_id');
        $school = School::findOrFail($schoolId);

        $validated = $request->validate([
            'orange_sms_api_url' => 'nullable|url',
            'orange_sms_client_id' => 'nullable|string',
            'orange_sms_client_secret' => 'nullable|string',
            'orange_sms_sender_name' => 'nullable|string|max:11',
            'sms_absence_template' => 'required|string',
            'sms_enabled' => 'boolean',
        ]);

        $school->update([
            'sms_enabled' => $request->boolean('sms_enabled'),
            'orange_sms_api_url' => $validated['orange_sms_api_url'] ?? 'https://api.orange.com/sms/v1',
            'orange_sms_client_id' => ! empty($validated['orange_sms_client_id']) ? Crypt::encryptString($validated['orange_sms_client_id']) : null,
            'orange_sms_client_secret' => ! empty($validated['orange_sms_client_secret']) ? Crypt::encryptString($validated['orange_sms_client_secret']) : null,
            'orange_sms_sender_name' => $validated['orange_sms_sender_name'] ?? null,
            'sms_absence_template' => $validated['sms_absence_template'],
        ]);

        return back()->with('success', '✅ Configuration SMS enregistrée avec succès !');
    }
}
