<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Services\ReportService;
class DashboardController extends Controller
{
    use ResolvesShop;
    public function index(ReportService $reports) { return response()->json($reports->dashboard($this->shopId())); }
}
