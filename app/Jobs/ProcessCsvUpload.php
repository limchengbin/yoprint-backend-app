<?php 

namespace App\Jobs;

use App\Events\UploadProcessed;
use App\Models\Upload;
use App\Services\CsvProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $upload;

    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    public function handle(CsvProcessingService $csvProcessor)
    {
        $result = $csvProcessor->process($this->upload);
    }

    public function failed(\Throwable $exception)
    {
        $this->upload->updateStatus('failed');
    }
}