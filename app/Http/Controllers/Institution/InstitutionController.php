<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use App\Models\Institution;

class InstitutionController extends Controller
{
    public function show(string $slug)
    {
        $institution = Institution::where('slug', $slug)->firstOrFail();

        return view('institution.show', compact('institution'));
    }
}
