<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderIssuePhoto extends Model
{
    protected $guarded = [];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(OrderIssue::class, 'order_issue_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk('public')->exists($this->path)
            ? Storage::disk('public')->url($this->path)
            : asset('images/store/no-image.svg');
    }
}
