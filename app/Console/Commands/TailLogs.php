<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TailLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:tail {--lines=50 : Número de líneas a mostrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostrar los logs en tiempo real (últimas líneas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            $this->error('Archivo de log no encontrado: ' . $logFile);
            return 1;
        }

        $lines = (int)$this->option('lines');
        $logContent = File::get($logFile);
        $logLines = explode("\n", $logContent);

        // Obtener las últimas N líneas
        $lastLines = array_slice($logLines, -($lines + 1));

        $this->info("📋 Últimas {$lines} líneas del log:\n");
        foreach ($lastLines as $line) {
            if (trim($line) !== '') {
                $this->line($line);
            }
        }

        return 0;
    }
}

