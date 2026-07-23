<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function __construct(private readonly HolidayRepositoryInterface $holidays) {}

    public function index(Request $request) { return HolidayResource::collection($this->holidays->paginate($request)); }

    public function store(StoreHolidayRequest $request)
    {
        $holiday = $this->holidays->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new HolidayResource($holiday), 201);
    }
}
