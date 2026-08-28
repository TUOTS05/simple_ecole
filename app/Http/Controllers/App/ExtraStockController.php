<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExtraStockItem;
use App\Models\ExtraStockMovement;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtraStockController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = session('current_school_id');

        $items = ExtraStockItem::where('school_id', $schoolId)->orderBy('name')->get();

        $movementsQuery = ExtraStockMovement::where('school_id', $schoolId)
            ->with(['item', 'student', 'processedBy'])
            ->orderByDesc('processed_at');

        $itemId = $request->get('item_id', '');
        if ($itemId) {
            $movementsQuery->where('extra_stock_item_id', $itemId);
        }

        $movements = $movementsQuery->paginate(20)->withQueryString();

        $students = Student::where('school_id', $schoolId)->orderBy('last_name')->orderBy('first_name')->get(['id', 'matricule', 'first_name', 'last_name']);

        return view('app.extras.stocks.index', compact('items', 'movements', 'itemId', 'students'));
    }

    public function itemsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'unit' => 'nullable|string|max:30',
            'unit_price' => 'required|numeric|min:0',
            'quantity_on_hand' => 'nullable|integer|min:0',
            'alert_threshold' => 'nullable|integer|min:0',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['unit'] = $validated['unit'] ?? 'unité';
        $validated['quantity_on_hand'] = $validated['quantity_on_hand'] ?? 0;

        $item = ExtraStockItem::create($validated);

        ActivityLog::logAction('extras.stock.item_created', "Création de l'article de stock « {$item->name} »");

        return back()->with('success', '✅ Article ajouté avec succès !');
    }

    public function itemsUpdate(Request $request, $id)
    {
        $item = ExtraStockItem::where('school_id', session('current_school_id'))->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'unit' => 'nullable|string|max:30',
            'unit_price' => 'required|numeric|min:0',
            'alert_threshold' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $item->update($validated);

        ActivityLog::logAction('extras.stock.item_updated', "Modification de l'article de stock « {$item->name} »");

        return back()->with('success', '✅ Article mis à jour avec succès !');
    }

    public function itemsDestroy($id)
    {
        $item = ExtraStockItem::where('school_id', session('current_school_id'))->findOrFail($id);

        if ($item->movements()->exists()) {
            return back()->withErrors(['error' => 'Impossible de supprimer un article ayant des mouvements de stock enregistrés. Désactivez-le plutôt.']);
        }

        $item->delete();

        return back()->with('success', '✅ Article supprimé avec succès !');
    }

    public function movementsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_stock_item_id' => 'required|exists:extra_stock_items,id',
            'type' => 'required|in:in,out,sale,return',
            'quantity' => 'required|integer|min:1',
            'student_id' => 'nullable|exists:students,id',
            'unit_price' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $item = ExtraStockItem::where('school_id', $schoolId)->findOrFail($validated['extra_stock_item_id']);

        $isOutbound = in_array($validated['type'], ExtraStockMovement::OUTBOUND_TYPES, true);

        if ($isOutbound && $validated['quantity'] > $item->quantity_on_hand) {
            return back()->withErrors(['quantity' => "Stock insuffisant : {$item->quantity_on_hand} {$item->unit}(s) disponible(s)."])->withInput();
        }

        DB::beginTransaction();
        try {
            ExtraStockMovement::create([
                'school_id' => $schoolId,
                'extra_stock_item_id' => $item->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'] ?? $item->unit_price,
                'student_id' => $validated['student_id'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $item->quantity_on_hand += $isOutbound ? -$validated['quantity'] : $validated['quantity'];
            $item->save();

            DB::commit();

            ActivityLog::logAction(
                'extras.stock.movement_created',
                ucfirst($validated['type'])." de {$validated['quantity']} {$item->unit}(s) « {$item->name} »"
            );

            return back()->with('success', '✅ Mouvement de stock enregistré avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Erreur : '.$e->getMessage()]);
        }
    }
}
