<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('category/Index', [
            'categories' => Category::paginate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        Category::create($validated);

        $this->flashSuccess('Tambah Kategori berhasil.');
        return Redirect::back();
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $category->update($validated);

        $this->flashSuccess('Update Kategori berhasil.');
        return Redirect::back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            $this->flashError('Kategori ini masih memiliki produk, tidak bisa dihapus.');
            return Redirect::back();
        }

        $category->delete();

        $this->flashSuccess('Hapus Kategori berhasil.');
        return Redirect::back();
    }
}
