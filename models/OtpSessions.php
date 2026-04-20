<?php

use Illuminate\Database\Eloquent\Model;

class OtpSessions extends Model
{
    protected $table = 'otp_sessions';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'otp',
        'type',
        'expires_at',
        'is_used',
        'qr_data',
        'created_at',
        'updated_at'
    ];

    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_used', false)->where('expires_at', '>', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function markAsUsed()
    {
        $this->is_used = true;
        $this->save();
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isValid()
    {
        return !$this->is_used && !$this->isExpired();
    }
}
