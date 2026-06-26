<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'industry',
        'website',
        'phone',
        'address',
    ];

    /**
     * Contacts belonging to this company.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Leads associated with this company.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
