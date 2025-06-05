<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use App\Models\ConsumptionLog;
use App\Models\Recommendation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = auth()->user()->load('profile');

            // Active Recommendation
            $activeRecommendation = Recommendation::whereHas('analysis', function ($query) use ($user) {
                $query->where('user_id', '=', $user->id);
            })->latest()
                ->first();

            // Last Analysis
            $lastAnalysis = Analysis::where('user_id', '=', $user->id)
                ->latest()
                ->first();

            // Favorite Herbal in 30 Days
            $thirtyDaysAgo = Carbon::today()->subDays(30)->startOfDay();
            $favoriteHerbalQuery = ConsumptionLog::where('user_id', '=', $user->id)
                ->where('consumed_at', '>=', $thirtyDaysAgo)
                ->select('herbal_medicine_name', DB::raw('count(*) as consumption_count'))
                ->groupBy('herbal_medicine_name')
                ->orderBy('consumption_count', 'desc')
                ->first();

            $favoriteHerbalData = null;
            if ($favoriteHerbalQuery) {
                $favoriteHerbalData = [
                    'name' => $favoriteHerbalQuery->herbal_medicine_name,
                    'frequency_info' => $favoriteHerbalQuery->consumption_count,
                ];
            }

            // Dashboard Data
            $dashboardData = [
                'greetingName' => $user->name,

                'activeRecommendation' => $activeRecommendation ? [
                    'herbal_medicine_name' => $activeRecommendation->recommended_herbal_medicine,
                    'note' => $activeRecommendation->recommendation_description,
                ] : null,

                'lastAnalysis' => $lastAnalysis ? [
                    'date' => $lastAnalysis->created_at,
                    'status_summary' => $lastAnalysis->status === 'completed' ? 'Selesai' : 'Belum Selesai',
                ] : null,

                'favoriteHerbal' => $favoriteHerbalData,
            ];

            return response()->json([
                'code' => 200,
                'message' => 'Dashboard fetched successfully',
                'data' => $dashboardData,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => 'Something went wrong',
                'data' => null,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
