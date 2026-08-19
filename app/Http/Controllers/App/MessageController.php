<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('current_school_id') ?? auth()->user()->school_id;
        
        $messages = Message::where('school_id', $schoolId)
            ->receivedFromParents()
            ->with('sender')
            ->orderBy('is_read', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $unreadCount = Message::where('school_id', $schoolId)
            ->receivedFromParents()
            ->where('is_read', false)
            ->count();
        
        return view('app.messages.index', compact('messages', 'unreadCount'));
    }
    
    public function show(Message $message)
    {
        $schoolId = session('current_school_id') ?? auth()->user()->school_id;
        
        if ($message->school_id !== $schoolId) {
            abort(403, 'Accès non autorisé.');
        }
        
        // Marquer comme lu automatiquement
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }
        
        $message->load('sender');
        
        return view('app.messages.show', compact('message'));
    }
    
    public function reply(Request $request, Message $message)
    {
        $schoolId = session('current_school_id') ?? auth()->user()->school_id;
        
        if ($message->school_id !== $schoolId) {
            abort(403, 'Accès non autorisé.');
        }
        
        $validated = $request->validate([
            'reply' => 'required|string|min:5',
        ]);
        
        $message->update([
            'reply' => $validated['reply'],
            'replied_at' => now(),
            'is_read' => true,
        ]);
        
        return redirect()->route('app.messages.index')
            ->with('success', 'Réponse envoyée avec succès !');
    }
}