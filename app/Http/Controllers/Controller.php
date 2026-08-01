<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function safeDb($callback, $fallbackCallback)
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return $callback();
        } catch (\Exception $e) {
            return $fallbackCallback();
        }
    }
}
