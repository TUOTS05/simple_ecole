<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BroadcastMessageController extends Controller
{
    public function create()
    {
        $schoolId = auth()->user()->school_id;

        // 1. Récupérer toutes les classes de l'école
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();

        // 2. Récupérer les parents ayant au moins un enfant dans cette école, avec leurs enfants
        $parents = User::whereHas('children', function ($q) use ($schoolId) {
            // ✅ CORRECTION : Préciser la table 'students'
            $q->where('students.school_id', $schoolId);
        })
            ->with(['children' => function ($q) use ($schoolId) {
                // ✅ CORRECTION : Préciser la table 'students'
                $q->where('students.school_id', $schoolId);
            }])
            ->orderBy('last_name')
            ->get()
            ->map(function ($parent) {
                // Création d'un nom affichable clair : "DUPONT Jean (Parent de : Emma, Lucas)"
                $childrenNames = $parent->children->map(fn ($c) => $c->first_name.' '.$c->last_name)->implode(', ');
                $parent->display_name = trim($parent->last_name.' '.$parent->first_name).' (Parent de : '.$childrenNames.')';

                return $parent;
            });

        return view('app.messages.broadcast', compact('classes', 'parents'));
    }

    public function store(Request $request)
    {
        // Validation conditionnelle : target_id requis si classe, receiver_id requis si parent
        $validated = $request->validate([
            'target_type' => 'required|in:all,class,parent',
            'target_id' => 'nullable|required_if:target_type,class|exists:school_classes,id',
            'receiver_id' => 'nullable|required_if:target_type,parent|exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $schoolId = auth()->user()->school_id;
        $senderId = auth()->id();
        $targetInfo = 'Tous les parents de l\'école'; // Valeur par défaut

        // 1. Déterminer le contexte (target_info)
        if ($validated['target_type'] === 'class') {
            $targetClass = SchoolClass::find($validated['target_id']);
            $targetInfo = $targetClass ? 'Classe : '.$targetClass->name : 'Classe spécifique';
        } elseif ($validated['target_type'] === 'parent') {
            // Récupérer les noms des enfants de ce parent dans cette école
            $parent = User::with(['children' => function ($q) use ($schoolId) {
                // ✅ CORRECTION : Préciser la table 'students' ici aussi
                $q->where('students.school_id', $schoolId);
            }])->find($validated['receiver_id']);

            $studentNames = $parent->children->map(fn ($s) => $s->first_name.' '.$s->last_name)->implode(', ');
            $targetInfo = 'Parent de : '.($studentNames ?: 'Élève non spécifié');
        }

        // 2. Créer UN SEUL message (plus de boucle foreach qui crée des doublons)
        DB::beginTransaction();
        try {
            if ($validated['target_type'] === 'parent') {
                // Envoi individuel : on définit le destinataire
                Message::create([
                    'school_id' => $schoolId,
                    'sender_id' => $senderId,
                    'receiver_id' => $validated['receiver_id'],
                    'target_info' => $targetInfo,
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'is_read' => false,
                ]);
            } else {
                // Envoi groupé (Tous ou Classe) : receiver_id = null (diffusion)
                // target_class_id restreint la visibilité aux parents de cette classe, sinon
                // (target_type = all) elle reste nulle et le message est visible par toute l'école.
                Message::create([
                    'school_id' => $schoolId,
                    'sender_id' => $senderId,
                    'receiver_id' => null,
                    'target_info' => $targetInfo,
                    'target_class_id' => $validated['target_type'] === 'class' ? $validated['target_id'] : null,
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'is_read' => false,
                ]);
            }

            DB::commit();

            $successMsg = $validated['target_type'] === 'parent'
                ? '✅ Message envoyé avec succès.'
                : "✅ Message de diffusion envoyé avec succès (Cible : {$targetInfo}).";

            return redirect()->route('app.messages.index')
                ->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Erreur lors de l\'envoi : '.$e->getMessage()])->withInput();
        }
    }
}
