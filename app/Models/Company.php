<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Setting\CompanyProfile;

class Company extends Model
{
    /**
     * Atributos permitidos para mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_company',
    ];

    /**
     * Casts de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'personal_company' => 'boolean',
    ];

    /**
     * Relação 1:1 com o perfil da empresa.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class, 'company_id');
    }
}
