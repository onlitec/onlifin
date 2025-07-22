<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_photo_url' => $this->profile_photo_url,
            'is_admin' => $this->is_admin,
            'is_active' => $this->is_active,
            'notifications' => [
                'email_notifications' => $this->email_notifications,
                'whatsapp_notifications' => $this->whatsapp_notifications,
                'push_notifications' => $this->push_notifications,
                'due_date_notifications' => $this->due_date_notifications,
            ],
            'security' => [
                'two_factor_enabled' => $this->two_factor_enabled,
                'two_factor_confirmed_at' => $this->two_factor_confirmed_at?->toISOString(),
            ],
            'social_auth' => [
                'google_connected' => !empty($this->google_id),
                'google_avatar' => $this->google_avatar,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
