<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PackageStationController extends Controller
{
    public function index()
    {
        return view('admin.package-station.index');
    }
    public function crossCheckPage()
    {
        return view('admin.package-station.cross-check');
    }
}
