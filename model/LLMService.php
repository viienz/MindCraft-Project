<?php

class LLMService
{
    public function askGroq($prompt)
    {
        $config = include __DIR__ . '/../config/llm.php';
        $apiKey = $config['api_key'];
        $endpoint = $config['endpoint'];
        $model = $config['model'];

        // Ambil data kategori dari database
        $categories = $this->getAllCategories();
        if (empty($categories)) {
            return "❌ Tidak bisa memuat kategori course dari database.";
        }

        $categoryList = implode(", ", $categories);

        $fullPrompt = "Kategori kursus yang tersedia di MindCraft: $categoryList.\n"
            . "Silakan bantu jawab pertanyaan dari user berikut ini, dan rekomendasikan kursus atau roadmap belajar yang sesuai:\n\n"
            . $prompt;

        $postData = [
            'model' => $model,
            'temperature' => 0.7,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Kamu adalah MindBot, asisten ramah dari platform e-learning MindCraft yang berfokus pada edukasi, skill modern, digital serta skill tradisional. Tugasmu membantu pengguna menemukan course terbaik dan menyarankan roadmap belajar berdasarkan kategori.
                    
                    **Penting:**
                    - Gunakan format Markdown untuk penekanan (tebal: **teks**) dan struktur (daftar: * item).
                    - Jika memberikan rekomendasi atau pilihan, gunakan daftar poin.
                    - Akhiri setiap respons dengan "Call to Action" yang jelas, menanyakan informasi lebih lanjut atau mengarahkan pengguna ke langkah berikutnya.
                    - **Fokuslah hanya pada topik yang berkaitan dengan kursus, roadmap belajar, atau informasi yang relevan dengan platform e-learning MindCraft. Jika pertanyaan di luar topik ini, sampaikan dengan sopan bahwa Anda hanya bisa membantu terkait topik MindCraft dan tawarkan bantuan terkait e-learning.**
                    - Hindari simbol atau bullet yang berlebihan di luar daftar poin Markdown. Jawabanmu harus menggunakan bahasa natural dan santai.'
                ],
                [
                    'role' => 'user',
                    'content' => $fullPrompt
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return "❌ Gagal menghubungi AI: $error_msg";
        }

        curl_close($ch);

        // Logging
        file_put_contents(__DIR__ . '/../logs/groq_raw_response.json', $result);
        $response = json_decode($result, true);
        file_put_contents(__DIR__ . '/../logs/groq_debug.log', var_export($response, true));

        if (isset($response['error'])) {
            return "❌ Error dari Groq: " . $response['error']['message'];
        }

        if (
            isset($response['choices']) &&
            is_array($response['choices']) &&
            isset($response['choices'][0]['message']['content'])
        ) {
            return $response['choices'][0]['message']['content'];
        }

        return "❌ Respons valid tapi tidak dikenali formatnya.";
    }

    private function getAllCategories()
    {
        include __DIR__ . '/../config/llmdatabase.php';

        $sql = "SELECT name FROM course_categories WHERE is_active = 1 ORDER BY sort_order ASC";
        $result = mysqli_query($conn, $sql);
        $categories = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row['name'];
            }
        }

        return $categories;
    }
}