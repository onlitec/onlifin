<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'message',
        'user_id',
        'is_read',
        'action_url',
        'action_text',
        'image_url',
        'category',
        'status',
        'scheduled_at',
        'send_to_all',
        'channels',
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'send_to_all' => 'boolean',
        'scheduled_at' => 'datetime',
        'channels' => 'array',
        'data' => 'array',
    ];
    
    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
    
    /**
     * Scope a query to only include sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }
    
    /**
     * Scope a query to only include scheduled notifications.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }
    
    /**
     * Mark the notification as read.
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
        
        return $this;
    }
    
    /**
     * Check if notification is for a specific channel
     */
    public function hasChannel($channel)
    {
        return in_array($channel, $this->channels ?? []);
    }
}
