<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';

    public $incrementing = false;

    protected $primaryKey = 'email'; // composite (tenant_id, email) — see migration; Eloquent single-PK is advisory only here, all queries below are explicit .where() calls

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'email', 'token', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
