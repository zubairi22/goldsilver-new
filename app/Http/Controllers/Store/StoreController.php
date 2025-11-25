<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreUpdateRequest;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(): Response
    {
        return inertia('store/Setting', [
            'settings' => StoreSetting::current()
        ]);
    }

    public function update(StoreUpdateRequest $request): RedirectResponse
    {
        $setting = StoreSetting::first();

        if ($setting) {
            $setting->update($request->validated());
        } else {
            StoreSetting::create($request->validated());
        }

        $this->flashSuccess('Berhasil mengubah pengaturan toko');

        return back();
    }
}
