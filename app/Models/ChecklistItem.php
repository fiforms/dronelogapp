<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    protected $fillable = [
        'template_id',
        'sort_order',
        'label',
        'has_comment_box',
    ];

    protected $casts = [
        'has_comment_box' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }
}
