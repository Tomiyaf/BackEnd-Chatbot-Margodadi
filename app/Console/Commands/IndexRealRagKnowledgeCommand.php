<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class IndexRealRagKnowledgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:index-real-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all official Pekon Margodadi Knowledge Base documents and 71 UMKMs into Python RAG Vector Store';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Memulai proses sinkronisasi dan vektorisasi data resmi Pekon Margodadi...');

        $baseDir = base_path();
        $envPython = env('RAG_PYTHON_PATH');
        $venvPythonWindows = $baseDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
        $venvPythonUnix = $baseDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';
        $venvUnixAlt = $baseDir . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';

        if (!empty($envPython) && file_exists($envPython)) {
            $pythonPath = $envPython;
        } elseif (file_exists($venvPythonWindows)) {
            $pythonPath = $venvPythonWindows;
        } elseif (file_exists($venvPythonUnix)) {
            $pythonPath = $venvPythonUnix;
        } elseif (file_exists($venvUnixAlt)) {
            $pythonPath = $venvUnixAlt;
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $pythonPath = 'python';
        } else {
            $pythonPath = 'python3';
        }

        $this->line("🐍 Menggunakan Python Interpreter: {$pythonPath}");

        $process = new Process([$pythonPath, '-m', 'src.index_real_data'], $baseDir, [
            'PYTHONIOENCODING' => 'utf-8',
        ]);
        $process->setTimeout(600); // 10 menit batas waktu

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('❌ Terjadi kesalahan saat menjalankan proses indexing RAG.');
            return Command::FAILURE;
        }

        $this->info('✨ Seluruh data berhasil diindeks ke AI Vector Store!');
        return Command::SUCCESS;
    }
}
