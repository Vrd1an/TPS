<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\C45Service;

class ConfusionMatrixController extends Controller
{
    public function index(C45Service $c45Service)
    {
        $evaluation = $c45Service->evaluateConfusionMatrix();
        return view('confusion-matrix.index', compact('evaluation'));
    }
}
