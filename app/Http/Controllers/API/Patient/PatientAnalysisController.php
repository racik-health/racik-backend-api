<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

            // create prompt for gemini ai
            $symptomsString = implode(',', $validated['main_symptoms']);
            $profileInfo = "";

            if ($user->profile) {
                if ($user->profile->allergies) {
                    $profileInfo .= "Pengguna memiliki alergi: " . $user->profile->allergies . "\n";
                }

                if ($user->profile->medical_conditions) {
                    $profileInfo .= "Pengguna memiliki kondisi medis: " . $user->profile->medical_conditions . "\n";
                }

                if ($user->profile->date_of_birth) {
                    $profileInfo .= "Pengguna lahir pada tanggal: " . $user->profile->date_of_birth . "\n";
                }
            }

            if (empty(trim($profileInfo))) {
                $profileInfo = "Pengguna tidak melaporkan alergi atau kondisi medis khusus.";
            }

            $prompt = "Sebagai seorang ahli herbal tradisional Indonesia, berikan rekomendasi jamu untuk pengguna dengan gejala: \"{$symptomsString}\". Deskripsi tambahan: \"{$validated['other_description']}\". Tingkat keparahan: {$validated['severity_level']}. Durasi gejala: {$validated['symptom_duration']}." . $profileInfo . " Mohon berikan rekomendasi dalam format berikut, pisahkan setiap bagian dengan baris baru dan label yang jelas:\n";
            $prompt .= "NAMA JAMU: [Nama Jamu yang Direkomendasikan]\n";
            $prompt .= "DESKRIPSI REKOMENDASI: [Penjelasan singkat mengapa jamu ini cocok dan manfaat utamanya]\n";
            $prompt .= "DETAIL PENGGUNAAN:\n";
            $prompt .= "- Aturan Pakai: [Aturan pakai jamu, misal: 2x sehari setelah makan]\n";
            $prompt .= "- Dosis: [Dosis per konsumsi, misal: 1 gelas atau 1 sendok makan]\n";
            $prompt .= "- Manfaat Tambahan: [Manfaat lain jika ada]\n";
            $prompt .= "- Peringatan: [Hal yang perlu diperhatikan atau efek samping jika ada, tulis 'Tidak ada' jika tidak ada peringatan khusus]\n";
            $prompt .= "TINGKAT KEPERCAYAAN: [Berikan estimasi persentase kepercayaan Anda terhadap rekomendasi ini, misal: 85%]";

            Log::info("Prompt to Gemini for analysis ID {$analysis->id}: " . $prompt);

            // call gemini ai
            $geminiApiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
            if (!$geminiApiKey) {
                throw new \Exception("GEMINI_API_KEY is not set");
            }

            $geminiApiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiApiKey}";

            $response = Http::timeout(60)->post($geminiApiUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful() && isset($response->json()['candidates'][0]['content']['parts'][0]['text'])) {
                $geminiTextResponse = $response->json()['candidates'][0]['content']['parts'][0]['text'];
                Log::info("Gemini response for analysis ID {$analysis->id}: " . $geminiTextResponse);

                // Simple parsing of Gemini response
                $parsedRecommendation = $this->parseGeminiResponse($geminiTextResponse);

                $analysis->recommendation()->create([
                    'recommended_herbal_medicine' => $parsedRecommendation['nama_jamu'] ?? 'Tidak ada rekomendasi spesifik',
                    'recommendation_description' => $parsedRecommendation['deskripsi_rekomendasi'] ?? 'AI tidak memberikan deskripsi.',
                    'herbal_medicine_details' => [
                        'usage_rules' => $parsedRecommendation['aturan_pakai'] ?? 'Sesuai anjuran umum.',
                        'dosage' => $parsedRecommendation['dosis'] ?? 'Secukupnya.',
                        'benefits' => $parsedRecommendation['manfaat_tambahan'] ?? 'Tidak ada informasi manfaat tambahan.',
                        'warnings' => $parsedRecommendation['peringatan'] ?? 'Tidak ada peringatan khusus.',
                        // 'daily_sessions' => $parsedRecommendation['daily_sessions'] ?? 2, // Asumsi default
                    ],
                    'ai_confidence_level' => $parsedRecommendation['tingkat_kepercayaan'] ?? null,
                    'raw_flask_response' => ['gemini_response' => $geminiTextResponse], // Simpan respons mentah Gemini
                ]);
                $analysis->status = 'completed';
                $analysis->save();

                return response()->json([
                    'code' => 200,
                    'message' => 'Analysis completed successfully',
                    'data' => $analysis->load('recommendation'),
                ], 200);
            } else {
                Log::error("Gemini API call failed for analysis ID {$analysis->id}. Status: " . $response->status() . " Body: " . $response->body());

                $analysis->status = 'analysis_failed';
                $analysis->save();

                throw new \Exception("Gemini API request failed: " . $response->body());
            }
        } catch (ConnectionException $e) {
            Log::error('ConnectionException to Gemini: ' . $e->getMessage());

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

    /**
     * Parse the response from Gemini API into a structured data.
     * The response is expected to be in plain text, with each line containing a key-value pair.
     * The key and value are separated by a colon (:).
     * The function will extract the following fields:
     * - nama_jamu (Jamu name)
     * - deskripsi_rekomendasi (Recommendation description)
     * - aturan_pakai (Usage rules)
     * - dosis (Dosage)
     * - manfaat_tambahan (Additional benefits)
     * - peringatan (Warning)
     * - tingkat_kepercayaan (Confidence level, as a decimal value between 0 and 1)
     * If a field is not found, it will be set to null or a default value.
     * @param string $textResponse The response from Gemini API
     * @return array The structured data
     */
    private function parseGeminiResponse(string $textResponse)
    {
        $data = [];
        $lines = explode("\n", $textResponse);
        foreach ($lines as $line) {
            if (str_starts_with($line, 'NAMA JAMU:')) {
                $data['nama_jamu'] = trim(str_replace('NAMA JAMU:', '', $line));
            } elseif (str_starts_with($line, 'DESKRIPSI REKOMENDASI:')) {
                $data['deskripsi_rekomendasi'] = trim(str_replace('DESKRIPSI REKOMENDASI:', '', $line));
            } elseif (str_starts_with($line, '- Aturan Pakai:')) {
                $data['aturan_pakai'] = trim(str_replace('- Aturan Pakai:', '', $line));
            } elseif (str_starts_with($line, '- Dosis:')) {
                $data['dosis'] = trim(str_replace('- Dosis:', '', $line));
            } elseif (str_starts_with($line, '- Manfaat Tambahan:')) {
                $data['manfaat_tambahan'] = trim(str_replace('- Manfaat Tambahan:', '', $line));
            } elseif (str_starts_with($line, '- Peringatan:')) {
                $data['peringatan'] = trim(str_replace('- Peringatan:', '', $line));
            } elseif (str_starts_with($line, 'TINGKAT KEPERCAYAAN:')) {
                preg_match('/(\d+)\%/', str_replace('TINGKAT KEPERCAYAAN:', '', $line), $matches);
                $data['tingkat_kepercayaan'] = isset($matches[1]) ? ((int) $matches[1] / 100) : null;
            }
        }
        // Jika ada field yang tidak terisi, berikan nilai default atau null
        $data['nama_jamu'] ??= 'Jamu Tidak Ditemukan';
        $data['deskripsi_rekomendasi'] ??= 'Tidak ada deskripsi.';
        $data['aturan_pakai'] ??= 'Sesuai anjuran.';
        $data['dosis'] ??= 'Secukupnya.';
        $data['manfaat_tambahan'] ??= 'Tidak ada.';
        $data['peringatan'] = ($data['peringatan'] ?? 'Tidak ada') === 'Tidak ada' ? null : $data['peringatan'];

        return $data;
    }
}
