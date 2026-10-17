<?php

namespace App\Console\Commands;

use App\Services\UmkmAiKnowledgeService;
use Illuminate\Console\Command;

class SyncUmkmRagCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'umkm:sync-rag {--id= : Sync specific UMKM ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize UMKM data to Python RAG AI Vector Store';

    /**
     * Execute the console command.
     */
    public function handle(UmkmAiKnowledgeService $service): int
    {
        $id = $this->option('id');

        if ($id) {
            $umkm = \App\Models\Umkm::find($id);
            if (!$umkm) {
                $this->error("UMKM dengan ID {$id} tidak ditemukan.");
                return 1;
            }

            $this->info("Menyinkronkan UMKM: {$umkm->name}...");
            $res = $service->syncToRag($umkm);
            $this->info("Hasil: " . json_encode($res));
            return 0;
        }

        $this->info('Menyinkronkan seluruh UMKM aktif ke AI Vector Store...');
        $result = $service->syncAll();

        $this->info("Selesai! Total: {$result['total']}, Berhasil: {$result['success']}, Gagal/Tertunda: {$result['failed']}, Total Chunks: {$result['total_chunks']}");
        return 0;
    }
}
