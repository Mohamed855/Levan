<?php

namespace App\Jobs\Tenants\DeleteBulk;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMassDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    protected $modelClass;
    protected $ids;
    protected $batchId;

    /*
   |--------------------------------------------------------------------------
   | Section Title
   |--------------------------------------------------------------------------
   | Description of this section.
   */
    public function __construct($modelClass, $ids, $batchId)
    {
        $this->modelClass = $modelClass;
        $this->ids = $ids;
        $this->batchId = $batchId;
    }

    /*
   |--------------------------------------------------------------------------
   | Section Title
   |--------------------------------------------------------------------------
   | Description of this section.
   */
    public function handle()
    {
        try {
            $deleted = $this->modelClass::whereIn('id', $this->ids)->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}