<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /** GET /api/faqs — публичный список активных FAQ */
    public function index()
    {
        $faqs = Faq::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'category', 'question', 'answer']);

        return response()->json($faqs);
    }

    /** GET /api/admin/faqs — все FAQ для админа */
    public function adminIndex()
    {
        return response()->json(
            Faq::orderBy('sort_order')->orderBy('id')->get()
        );
    }

    /** POST /api/admin/faqs */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category'   => 'required|string|max:50',
            'question'   => 'required|string|max:1000',
            'answer'     => 'required|string|max:5000',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'nullable|boolean',
        ]);

        $faq = Faq::create($data);

        return response()->json($faq, 201);
    }

    /** PUT /api/admin/faqs/{id} */
    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $data = $request->validate([
            'category'   => 'sometimes|string|max:50',
            'question'   => 'sometimes|string|max:1000',
            'answer'     => 'sometimes|string|max:5000',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'nullable|boolean',
        ]);

        $faq->update($data);

        return response()->json($faq);
    }

    /** DELETE /api/admin/faqs/{id} */
    public function destroy($id)
    {
        Faq::findOrFail($id)->delete();

        return response()->json(['message' => 'FAQ удалён']);
    }
}
