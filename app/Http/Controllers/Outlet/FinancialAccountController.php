<?php

namespace App\Http\Controllers\Outlet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\OutletFinancialAccountCreateRequest;
use App\Http\Requests\Outlet\OutletFinancialAccountUpdateRequest;
use App\Models\FinancialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class FinancialAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('outlet/Account', [
            'accounts' => FinancialAccount::paginate(),
        ]);
    }

    public function store(OutletFinancialAccountCreateRequest $request): RedirectResponse
    {
        FinancialAccount::create($request->validated());

        $this->flashSuccess('Tambah Akun Keuangan Berhasil.');
        return Redirect::back();
    }

    public function update(OutletFinancialAccountUpdateRequest $request, FinancialAccount $financial_account): RedirectResponse
    {
        $financial_account->update($request->validated());

        $this->flashSuccess('Update Akun Keuangan Berhasil.');
        return Redirect::back();
    }

    public function destroy(FinancialAccount $financial_account): RedirectResponse
    {
        $financial_account->delete();

        $this->flashSuccess('Hapus Akun Keuangan Berhasil.');
        return Redirect::back();
    }
}
