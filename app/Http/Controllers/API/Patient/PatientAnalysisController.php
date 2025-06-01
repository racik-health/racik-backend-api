<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use App\Services\AI\GeminiAnalysisService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatientAnalysisController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiAnalysisService $geminiAnalysisService)
    {
        $this->geminiService = $geminiAnalysisService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = auth()->user();

            $analyses = Analysis::where('user_id', '=', $user->id)
                ->latest()
                ->get();

            return response()->json([
                'code' => 200,
                'message' => 'Analyses fetched successfully',
                'data' => $analyses->load('recommendation'),
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
        $validated = $request->validate([
            'main_symptoms' => 'required|array|min:1',
            'main_symptoms.*' => 'required|string|max:255',
            'other_description' => 'nullable|string|max:1000',
            'severity_level' => 'required|string|in:mild,moderate,severe',
            'symptom_duration' => 'required|string|max:255',
        ]);

        $analysis = null;

        try {
            $user = auth()->user()->load('profile');
            $analysis = $user->analyses()->create($validated + ['status' => 'pending_recommendation']);

            $recommendationDataFromAI = $this->geminiService->getRecommendation($user, $validated);

            if ($recommendationDataFromAI) {
                $analysis->recommendation()->create([
                    'recommended_herbal_medicine' => $recommendationDataFromAI['recommended_herbal_medicine'],
                    'recommendation_description' => $recommendationDataFromAI['recommendation_description'],
                    'herbal_medicine_details' => $recommendationDataFromAI['herbal_medicine_details'],
                    'ai_confidence_level' => $recommendationDataFromAI['ai_confidence_level'],
                    'raw_flask_response' => $recommendationDataFromAI['raw_gemini_response'],
                ]);

                $analysis->status = 'completed';
            } else {
                $analysis->status = 'analysis_failed';
            }

            $analysis->save();

            return response()->json([
                'code' => 200,
                'message' => 'Analysis completed successfully',
                'data' => $analysis->load('recommendation'),
            ], 200);
        } catch (ConnectionException $e) {
            if ($analysis) {
                $analysis->status = 'analysis_failed';
                $analysis->save();
            }

            return response()->json([
                'code' => 500,
                'message' => 'Connection to Gemini API failed',
                'data' => null,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());

            if ($analysis) {
                $analysis->status = 'analysis_failed';
                $analysis->save();
            }

            return response()->json([
                'code' => 500,
                'message' => 'Something went wrong',
                'data' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();

        // if ($user->id !== $id) {
        //     return response()->json([
        //         'code' => 403,
        //         'message' => 'Forbidden',
        //         'data' => null,
        //     ], 403);
        // }

        $analysis = Analysis::where('user_id', '=', $user->id)
            ->where('id', '=', $id)
            ->first();

        if (!$analysis) {
            return response()->json([
                'code' => 404,
                'message' => 'Analysis not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Analysis fetched successfully',
            'data' => $analysis->load('recommendation'),
        ], 200);
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
