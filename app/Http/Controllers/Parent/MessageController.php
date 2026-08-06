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
        
        $messages = Message::where('sender_id', $parent->id)
            ->with('school')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('parent.messages.index', compact('messages'));
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
        
        // ✅ CORRECTION : Préciser la table 'students'
        $hasChildInSchool = $parent->children()
            ->where('students.school_id', $validated['school_id'])
            ->exists();
        
        if (!$hasChildInSchool) {
            return back()->withErrors(['school_id' => 'Vous n\'avez pas d\'enfant dans cette école.'])
                        ->withInput();
        }
        
        Message::create([
            'school_id' => $validated['school_id'],
            'sender_id' => $parent->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);
        
        return redirect()->route('parent.messages.index')
            ->with('success', 'Message envoyé avec succès !');
    }
    
    public function show(Message $message)
    {
        $parent = auth()->user();
        
        if ($message->sender_id !== $parent->id) {
            abort(403);
        }
        
        $message->load('school');
        
        return view('parent.messages.show', compact('message'));
    }
}