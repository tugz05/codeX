<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Criteria;
use App\Models\CriteriaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class CriteriaController extends Controller
{
    public function index()
    {
        $criteria = Criteria::with('items')
            ->where('user_id', Auth::id())
            ->orderBy('created_at','desc')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description,
                'grading_method' => $c->grading_method,
                'items' => $c->items->map(fn($i) => [
                    'id' => $i->id,
                    'label' => $i->label,
                    'description' => $i->description,
                    'points' => $i->points,
                    'weight' => $i->weight,
                    'sort_order' => $i->sort_order,
                ]),
            ]);

        return Inertia::render('Instructor/Criteria/Index', [
            'criteria' => $criteria,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:150', Rule::unique('criterias','title')->where('user_id', Auth::id())],
            'description' => ['nullable','string','max:2000'],
            'grading_method' => ['required','in:sum,average,custom'],
            'items' => ['array','min:1'],
            'items.*.label' => ['required','string','max:150'],
            'items.*.description' => ['nullable','string','max:1000'],
            'items.*.weight' => ['nullable','numeric','min:0','max:100'],
            'items.*.sort_order' => ['nullable','integer','min:0','max:255'],
        ]);

        $criteria = Criteria::create([
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'grading_method' => $data['grading_method'],
        ]);

        foreach ($data['items'] ?? [] as $idx => $item) {
            CriteriaItem::create([
                'criteria_id' => $criteria->id,
                'label' => $item['label'],
                'description' => $item['description'] ?? null,
                'weight' => $item['weight'] ?? null,
                'sort_order' => $item['sort_order'] ?? $idx,
            ]);
        }

        return back()->with('success','Criteria created.');
    }

    public function update(Request $request, Criteria $criteria)
    {
        abort_unless($criteria->user_id === Auth::id(), 403);

        $data = $request->validate([
            'title' => ['required','string','max:150', Rule::unique('criterias','title')->where('user_id', Auth::id())->ignore($criteria->id)],
            'description' => ['nullable','string','max:2000'],
            'grading_method' => ['required','in:sum,average,custom'],
            'items' => ['array','min:1'],
            'items.*.id' => ['nullable','integer'],
            'items.*.label' => ['required','string','max:150'],
            'items.*.description' => ['nullable','string','max:1000'],
            'items.*.weight' => ['nullable','numeric','min:0','max:100'],
            'items.*.sort_order' => ['nullable','integer','min:0','max:255'],
        ]);

        $criteria->updateOrCreate([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'grading_method' => $data['grading_method'],

        ]);

        // Sync items: upsert by id, remove missing
        $incomingIds = collect($data['items'])->pluck('id')->filter()->all();
        // delete removed
        CriteriaItem::where('criteria_id', $criteria->id)
            ->whereNotIn('id', $incomingIds ?: [0])
            ->delete();

        foreach ($data['items'] as $idx => $item) {
            CriteriaItem::updateOrCreate(
                ['id' => $item['id'] ?? 0, 'criteria_id' => $criteria->id],
                [
                    'label' => $item['label'],
                    'description' => $item['description'] ?? null,
                    'weight' => $item['weight'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $idx,
                ]
            );
        }

        return back()->with('success','Criteria updated.');
    }

    public function destroy(Criteria $criteria)
    {
        abort_unless($criteria->user_id === Auth::id(), 403);
        $criteria->delete();
        return back()->with('success','Criteria deleted.');
    }

    /** For Activity Create: return instructor's criteria (id + basic meta) */
    public function options()
    {
        return Criteria::where('user_id', Auth::id())
            ->orderBy('title')
            ->get(['id','title','grading_method']);
    }
}
