<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PatientAnalysisController extends Controller
{
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
        // TODO: implement store data from dashboard and send to flask server
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();

        if ($user->id !== $id) {
            return response()->json([
                'code' => 403,
                'message' => 'Forbidden',
                'data' => null,
            ], 403);
        }

        $analysis = Analysis::where('user_id', '=', $user->id)
            ->where('id', '=', $id)
            ->first();

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
