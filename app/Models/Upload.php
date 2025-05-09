<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;

    protected $guarded  = [];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M d, Y H:i:s');
    }


    public function updateStatus($status, $processedRows = null)
    {
        $this->status = $status;

        if ($processedRows !== null) {
            $this->processed_rows = $processedRows;
        }

        $this->save();
    }
}
