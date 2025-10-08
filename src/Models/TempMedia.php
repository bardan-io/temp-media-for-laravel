<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Models;

use BardanIO\TempMedia\Database\Factories\TempMediaFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Temporary Media Model
 *
 * @property int $id
 * @property string|null $session_id
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property Carbon $expires_at
 * @property bool $is_processed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TempMedia extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'session_id',
        'original_name',
        'mime_type',
        'size',
        'expires_at',
        'is_processed',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_processed' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $collectionName = config('temp-media.collection_name', 'temp_files');
        $allowedMimeTypes = config('temp-media.allowed_mime_types', []);

        $collection = $this->addMediaCollection($collectionName)->singleFile();

        if (! empty($allowedMimeTypes)) {
            $collection->acceptsMimeTypes($allowedMimeTypes);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (! config('temp-media.generate_conversions', false)) {
            return;
        }

        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('small')
            ->width(150)
            ->height(150)
            ->nonQueued();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
            ->where('is_processed', false);
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    protected static function newFactory()
    {
        return TempMediaFactory::new();
    }

    public function getCollectionName(): string
    {
        return config('temp-media.collection_name', 'temp_files');
    }
}
