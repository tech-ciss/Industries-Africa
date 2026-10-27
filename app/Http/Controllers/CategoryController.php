<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Category::class);
        $categories = Category::withCount('tickets')->latest()->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('create', Category::class);

        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Catégorie créée.');
    }

    public function show(Category $category)
    {
        return redirect()->route('categories.edit', $category);
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);
        $data = $request->validate([
            'name' => ['required', 'min:3', 'max:255', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'max:1000'],
        ]);
        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        // Les tickets sont supprimés en cascade : on bloque si la catégorie est utilisée.
        if ($category->tickets()->exists()) {
            return back()->withErrors([
                'category' => 'Cette catégorie est utilisée par des tickets et ne peut pas être supprimée.',
            ]);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée.');
    }
}
