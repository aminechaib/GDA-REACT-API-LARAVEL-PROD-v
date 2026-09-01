<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductExport extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'file_path',
        'filename',
        'keyword_count',
        'max_results',
        'keyword_index',
        'matched_rows',
        'current_keyword',
        'error',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
