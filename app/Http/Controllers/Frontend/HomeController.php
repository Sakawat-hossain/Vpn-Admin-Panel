<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\GeneratedImage;

class HomeController extends Controller
{
    public function index()
    {
        return view('frontend.home');
    }
}
