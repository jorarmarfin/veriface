<?php

namespace App\Console\Commands;

use App\Models\People;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixPhotoPathFormat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:photo-paths';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corregir formato de photo_path removiendo storage/app/public/';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $people = People::whereNotNull('photo_path')->get();
        $fixed = 0;

        foreach ($people as $person) {
            if (Str::startsWith($person->photo_path, 'storage/app/public/')) {
                $correctedPath = str_replace('storage/app/public/', '', $person->photo_path);
                $person->photo_path = $correctedPath;
                $person->save();
                $fixed++;
                $this->line("✅ Corregido: {$person->document_number} -> {$correctedPath}");
            }
        }

        $this->info("\n✅ Se corrigieron {$fixed} rutas de foto");
    }
}

