<?php

namespace App\Http\Controllers;

use App\Http\Requests\Outlet\OutletSettingUpdateRequest;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutletController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('outlet/Profile' , [
            'outlet' => Outlet::first()
        ]);
    }

    public function update(OutletSettingUpdateRequest $request): RedirectResponse
    {
        $outlet = Outlet::first();
        if ($outlet) {
            $outlet->update($request->validated());
        } else {
            Outlet::create($request->validated());
        }
        $this->flashSuccess('Berhasil mengubah data outlet');
        return back();
    }
}
