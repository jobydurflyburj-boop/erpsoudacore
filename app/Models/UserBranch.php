<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class UserBranch extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = ['tenant_id', 'user_id', 'branch_id'];
}
