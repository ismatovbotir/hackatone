<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'media_path', 'media_type', 'draft_path', 'draft_type', 'job_id', 'job_started_at', 'job_error', 'prompt'])]
class ActivityVideo extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['job_started_at' => 'datetime'];
    }

    public function generating(): bool
    {
        return $this->job_id !== null;
    }
}
