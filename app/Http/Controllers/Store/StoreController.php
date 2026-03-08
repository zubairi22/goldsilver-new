<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreUpdateRequest;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(string $category): Response
    {
        $setting = StoreSetting::where('category', $category)->firstOrFail();

        return inertia('store/Setting', [
            'setting' => $setting,
            'category' => $category,
        ]);
    }

    public function update(StoreUpdateRequest $request, string $category): RedirectResponse
    {
        $setting = StoreSetting::where('category', $category)->first();

        if ($setting) {
            $setting->update($request->validated());
        } else {
            $setting = StoreSetting::create([
                ...$request->validated(),
                'category' => $category,
            ]);
        }

        if ($request->hasFile('logo')) {
            $setting->clearMediaCollection('store-logo');
            $setting->addMediaFromRequest('logo')->toMediaCollection('store-logo');
        }

        $this->flashSuccess('Berhasil mengubah pengaturan toko');

        return back();
    }
}