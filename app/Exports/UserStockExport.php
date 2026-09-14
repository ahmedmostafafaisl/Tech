<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class UserStockExport implements FromView
{



    public function view(): View
    {



        $users = User::where('type', 'tech')
            ->whereHas('stock') // ✅ only techs with stock
            ->with(['stock.items', 'stock.parts'])
            ->get(['id', 'username', 'tech_id', 'technician_rec_id', 'warehouse_id']);


        return view('exports.user_stock', [
            'users' => $users,
        ]);
    }
}
