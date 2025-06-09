<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;

class DispenseController extends Controller
{
    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
            'herbal_medicine_name' => 'required|string',
            'analysis_id' => 'required|integer|exists:analyses,id',
            // 'dispenser_id' => 'required|string',
        ]);

        $herbalName = $validated['herbal_medicine_name'];
        $analysisId = $validated['analysis_id'];
        // TODO: Replace with actual dispenser id 
        $dispenserId = 'dispenser_1';

        $recipes = config('herbal_recipes.recipes');

        if (!isset($recipes[$herbalName])) {
            return response()->json([
                'code' => 404,
                'message' => 'Herbal medicine not found',
            ], 404);
        }

        $commandData = [
            'command_id' => "analysis_{$analysisId}",
            'herbal_name' => $herbalName,
            'status' => 'pending',
            'recipe' => $recipes[$herbalName],
            'error_message' => null,
            'timestamp' => round(microtime(true) * 1000),
        ];

        try {
            $this->database
                ->getReference("dispensers_commands/{$dispenserId}")
                ->set($commandData);

            Log::info('Dispense command sent to Firebase for dispenser: ' . $dispenserId, $commandData);

            return response()->json([
                'code' => 200,
                'message' => 'Command sent to dispenser successfully',
                'data' => [
                    'dispenser_id' => $dispenserId,
                    'command_id' => $commandData['command_id'],
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Dispense error: ' . $e->getMessage(), [
                'analysis_id' => $analysisId,
                'herbal_name' => $herbalName,
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Failed to send command to the dispenser. A server error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
