<?php
namespace App\Repositories\Eloquent;
use App\Models\AiSetting;
use App\Repositories\Contracts\AiSettingRepositoryInterface;
class AiSettingRepository extends BaseRepository implements AiSettingRepositoryInterface
{
    protected string $modelClass = AiSetting::class;
}
