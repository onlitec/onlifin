<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    /**
     * Tabela associada.
     *
     * @var string
     */
    protected $table = 'company_profiles';

    /**
     * Atributos permitidos para mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'logo',
        'phone_number',
        'email',
        'tax_id',
        'entity_type',
        'chatbot_enabled',
    ];

    /**
     * Casts de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'chatbot_enabled' => 'boolean',
    ];

    /**
     * Relação inversa com Company.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }
} 