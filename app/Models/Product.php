<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded  = [];

    public function setProductDescriptionAttribute($value)
    {
        $this->attributes['product_description'] = $this->cleanNonUtf8($value);
    }

    public function setProductTitleAttribute($value)
    {
        $this->attributes['product_title'] = $this->cleanNonUtf8($value);
    }

    protected function cleanNonUtf8($string)
    {
        if ($string === null) return null;
        
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
}