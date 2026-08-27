<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $parent = auth()->user();

        // Le parent voit les messages qu'il a envoyés, ceux reçus personnellement, et les
        // diffusions de son école qui ciblent "tous les parents" ou la classe d'un de ses enfants.
        $messages = Message::visibleToParent($parent)
            ->with('school')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Seuls les messages personnels entrent dans le badge de notification (voir Message::scopeUnreadForParent).
        $unreadCount = Message::unreadForParent($parent)->count();

        return view('parent.messages.index', compact('messages', 'unreadCount'));
    }

    public function create()
    {
        $parent = auth()->user();

        $schools = $parent->children()
            ->with('school')
            ->get()
            ->pluck('school')
            ->unique('id');

        return view('parent.messages.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        $parent = auth()->user();

        $hasChildInSchool = $parent->children()
            ->where('students.school_id', $validated['school_id'])
            ->exists();

        if (! $hasChildInSchool) {
            return back()->withErrors(['school_id' => 'Vous n\'avez pas d\'enfant dans cette école.'])
                ->withInput();
        }

        Message::create([
            'school_id' => $validated['school_id'],
            'sender_id' => $parent->id,
            'receiver_id' => null, // L'école reçoit globalement
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        return redirect()->route('parent.messages.index')
            ->with('success', 'Message envoyé avec succès !');
    }

    public function show(Message $message)
    {
        $parent = auth()->user();

        // Sécurité : le parent ne peut voir que ses propres messages et les diffusions qui le concernent
        if (! Message::visibleToParent($parent)->where('id', $message->id)->exists()) {
            abort(403, 'Vous n\'êtes pas autorisé à voir ce message.');
        }

        $message->load('school');

        // Marquer automatiquement comme lu si le parent est le destinataire
        if ($message->receiver_id === $parent->id && ! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('parent.messages.show', compact('message'));
    }
}
