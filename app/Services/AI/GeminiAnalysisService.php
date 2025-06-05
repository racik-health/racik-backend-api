<?php

namespace App\Services\AI;

use App\Models\User;
use DragonCode\Support\Facades\Helpers\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAnalysisService
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->apiKey}";
    }

    public function getRecommendation(User $user, array $symptomsData)
    {
        if (!$this->apiKey) {
            throw new \Exception("API key for Gemini is not set");
        }

        $prompt = $this->buildPrompt($user, $symptomsData);
        Log::info("Prompt to Gemini for user ID {$user->id}: " . $prompt);

        $response = Http::timeout(60)->post($this->apiUrl, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                // 'maxOutputTokens' => 800,
            ]
        ]);

        if ($response->successful() && isset($response->json()['candidates'][0]['content']['parts'][0]['text'])) {
            $geminiTextResponse = $response->json()['candidates'][0]['content']['parts'][0]['text'];
            Log::info("Gemini response for user ID {$user->id}: " . $geminiTextResponse);

            $parsedData = $this->parseGeminiResponse($geminiTextResponse);
            $parsedData['raw_gemini_response'] = ['gemini_response' => $geminiTextResponse];
            return $parsedData;
        } else {
            Log::error("Gemini API call failed for user ID {$user->id}. Status: " . $response->status() . " Body: " . $response->body());
            throw new \Exception("Gemini API call failed");
        }
    }

    private function buildPrompt(User $user, array $symptomsData)
    {
        $symptomsString = implode(", ", $symptomsData['main_symptoms']);
        $profileInfo = "";

        if ($user->profile) {
            if ($user->profile->allergies) {
                $profileInfo .= " Pengguna memiliki alergi: " . $user->profile->allergies . ".";
            }
            if ($user->profile->medical_conditions) {
                $profileInfo .= " Pengguna memiliki kondisi medis: " . $user->profile->medical_conditions . ".";
            }
            if ($user->profile->date_of_birth) {
                $profileInfo .= " Pengguna lahir pada tanggal: " . $user->profile->date_of_birth . ".";
            }
        }

        if (empty(trim($profileInfo))) {
            $profileInfo = " Pengguna tidak melaporkan alergi atau kondisi medis khusus.";
        }

        $prompt = "Anda adalah seorang ahli herbal tradisional Indonesia yang sangat berpengalaman dan ahli dalam pengobatan herbal.\n";
        $prompt .= "Tugas Anda adalah memberikan rekomendasi jamu yang paling sesuai berdasarkan informasi pengguna berikut (pastikan Anda mempertahankan privasi pengguna):\n";
        $prompt .= "- Gejala Utama: \"{$symptomsString}\"\n";
        $prompt .= "- Deskripsi Tambahan: \"{$symptomsData['other_description']}\"\n";
        $prompt .= "- Tingkat Keparahan: {$symptomsData['severity_level']}\n";
        $prompt .= "- Durasi Gejala: {$symptomsData['symptom_duration']}\n";
        $prompt .= "- Informasi Profil Pengguna:{$profileInfo}\n\n";
        $prompt .= "Mohon berikan rekomendasi jamu yang paling sesuai. Sertakan informasi berikut dalam format yang jelas dan terstruktur, gunakan label yang SAMA PERSIS seperti di bawah ini, dan pisahkan setiap bagian dengan BARIS BARU:\n\n";
        $prompt .= "NAMA_JAMU: [Nama Jamu yang Direkomendasikan]\n";
        $prompt .= "DESKRIPSI_REKOMENDASI: [Penjelasan singkat mengapa jamu ini cocok, manfaat utamanya untuk gejala yang dilaporkan, dan cara kerja jamu secara umum]\n";
        $prompt .= "ATURAN_PAKAI: [Aturan pakai jamu, misal: 2x sehari setelah makan, pagi dan sore]\n";
        $prompt .= "DOSIS: [Dosis per konsumsi, misal: 1 gelas (sekitar 200ml) atau 1 sendok makan]\n";
        $prompt .= "CARA_PEMBUATAN: [Jika relevan, jelaskan cara pembuatan sederhana jamu tersebut. Jika tidak, tulis 'Tidak ada instruksi pembuatan khusus, dapat dibeli jadi atau ikuti petunjuk kemasan.']\n";
        $prompt .= "MANFAAT_TAMBAHAN: [Manfaat lain dari jamu ini jika ada, misal: Meningkatkan nafsu makan, Menjaga stamina. Tulis 'Tidak ada' jika tidak ada manfaat tambahan signifikan.]\n";
        $prompt .= "PERINGATAN: [Hal yang perlu diperhatikan, potensi efek samping, atau untuk siapa jamu ini tidak cocok. Tulis 'Tidak ada peringatan khusus' jika tidak ada.]\n";
        $prompt .= "TINGKAT_KEPERCAYAAN_AI: [Berikan estimasi persentase kepercayaan Anda terhadap rekomendasi ini dalam format angka saja, misal: 85]";

        return $prompt;
    }

    private function parseGeminiResponse(string $textResponse): array
    {
        $data = [];
        $lines = explode("\n", $textResponse);
        $currentKey = null;
        $buffer = "";

        $keyMapping = [
            'NAMA_JAMU' => 'recommended_herbal_medicine',
            'DESKRIPSI_REKOMENDASI' => 'recommendation_description',
            'ATURAN_PAKAI' => 'usage_rules',
            'DOSIS' => 'dosage',
            'CARA_PEMBUATAN' => 'preparation_method',
            'MANFAAT_TAMBAHAN' => 'additional_benefits',
            'PERINGATAN' => 'warnings',
            'TINGKAT_KEPERCAYAAN_AI' => 'ai_confidence_level_text',
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            $matched = false;
            foreach ($keyMapping as $label => $dataKey) {
                if (str_starts_with($line, $label . ':')) {
                    if ($currentKey && !empty(trim($buffer))) {
                        $data[$keyMapping[$currentKey]] = trim($buffer);
                    }
                    $currentKey = $label;
                    $buffer = trim(Str::after($line, $label . ':'));
                    $matched = true;
                    break;
                }
            }
            if (!$matched && $currentKey) {
                $buffer .= "\n" . $line;
            }
        }
        if ($currentKey && !empty(trim($buffer))) {
            $data[$keyMapping[$currentKey]] = trim($buffer);
        }

        if (isset($data['ai_confidence_level_text'])) {
            preg_match('/(\d+)/', $data['ai_confidence_level_text'], $matches);
            $data['ai_confidence_level'] = isset($matches[1]) ? ((int) $matches[1] / 100) : null;
            unset($data['ai_confidence_level_text']);
        } else {
            $data['ai_confidence_level'] = null;
        }

        return [
            'recommended_herbal_medicine' => $data['recommended_herbal_medicine'] ?? 'Tidak ada rekomendasi spesifik',
            'recommendation_description' => $data['recommendation_description'] ?? 'Tidak ada deskripsi rekomendasi.',
            'herbal_medicine_details' => [
                'usage_rules' => $data['usage_rules'] ?? 'Sesuai anjuran umum.',
                'dosage' => $data['dosage'] ?? 'Secukupnya.',
                'preparation_method' => $data['preparation_method'] ?? 'Tidak ada instruksi pembuatan khusus.',
                'benefits' => $data['additional_benefits'] ?? 'Tidak ada informasi manfaat tambahan.',
                'warnings' => ($data['warnings'] ?? 'Tidak ada peringatan khusus.') === 'Tidak ada peringatan khusus' ? null : $data['warnings'],
            ],
            'ai_confidence_level' => $data['ai_confidence_level'] ?? 0.75,
        ];
    }
}
