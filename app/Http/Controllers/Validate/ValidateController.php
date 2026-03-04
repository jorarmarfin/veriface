<?php

namespace App\Http\Controllers\Validate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ValidateController extends Controller
{
    public function index($uuid)
    {
        return view('validate.index');
    }
}
