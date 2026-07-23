<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsIssueItem extends Model
{
    use BelongsToTenant, HasUuid;

    public $timestamps = false;
    protected $fillable = ['tenant_id', 'goods_issue_id', 'product_id', 'quantity'];
    protected $casts = ['quantity' => 'decimal:3'];

    public function goodsIssue(): BelongsTo { return $this->belongsTo(GoodsIssue::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
