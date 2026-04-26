<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/validate/{uuid}',[App\Http\Controllers\Validate\ValidateController::class, 'index'])->name('validate');
Route::post('/validate/{uuid}/analyze',[App\Http\Controllers\Validate\ValidateController::class, 'analyzeFace'])->name('validate.analyze');

Route::get('/i/{slug}', [\App\Http\Controllers\Institution\InstitutionController::class, 'show'])->name('institution.show');

Route::get('/export/validation-logs', function (Illuminate\Http\Request $request) {
    abort_unless(auth()->check(), 403);

    $institution = \App\Models\Institution::findOrFail($request->integer('institution_id'));

    $matched = null;
    if ($request->filled('matched')) {
        $matched = filter_var($request->input('matched'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    $filename = 'validaciones-' . $institution->slug . '-' . now()->format('Ymd-His') . '.xlsx';

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\ValidationLogsExport(
            institutionId: $institution->id,
            matched:       $matched,
            dateFrom:      $request->input('date_from'),
            dateTo:        $request->input('date_to'),
        ),
        $filename
    );
})->name('validation-logs.export')->middleware('web');

Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
