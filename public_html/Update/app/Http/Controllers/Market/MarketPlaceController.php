<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarketPlaceController extends Controller
{
    public function index()
    {
        return view('panel.admin.market.index');
    }
}
