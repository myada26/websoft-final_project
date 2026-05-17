<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiInsightService // [AI Narrator]
{
    // [perf] Cache narrative for 5 min keyed by the stat snapshot — stats only change
    // when a transaction is made, and a 5-min staleness is acceptable for a narrative.
    private const NARRATIVE_TTL_SECONDS = 300;

    public function generateNarrative(array $data): string // [AI Narrator]
    {
        $contextText = $this->buildContextText($data); // [AI Narrator]

        // [perf] Skip Gemini round-trip if the same context was rendered recently.
        $cacheKey = 'ai_narrative:' . md5($contextText);

        return Cache::remember($cacheKey, self::NARRATIVE_TTL_SECONDS,
            fn () => $this->callGemini($this->systemInstruction(), $contextText)
        );
    }

    public function answerQuestion(array $data, string $question): string // [AI Narrator]
    {
        $contextText = $this->buildContextText($data); // [AI Narrator]

        $userMessage = "{$contextText}\n\nOfficer question: {$question}\n\nAnswer in 2-3 sentences maximum."; // [AI Narrator]

        return $this->callGemini($this->systemInstruction(), $userMessage); // [AI Narrator]
    }

    private function callGemini(string $systemInstruction, string $userMessage): string // [AI Narrator]
    {
        $model = config('services.gemini.model'); // [AI Narrator]
        $key   = config('services.gemini.key'); // [AI Narrator]
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}"; // [AI Narrator]

        // [AI Narrator] system_instruction is unreliable on Flash — inject system prompt directly into user message
        $combinedMessage = "SYSTEM INSTRUCTIONS:\n{$systemInstruction}\n\nDATA:\n{$userMessage}"; // [AI Narrator]

        $body = [ // [AI Narrator]
            'contents' => [ // [AI Narrator]
                [ // [AI Narrator]
                    'role'  => 'user', // [AI Narrator]
                    'parts' => [['text' => $combinedMessage]], // [AI Narrator]
                ], // [AI Narrator]
            ], // [AI Narrator]
            'generationConfig' => [ // [AI Narrator]
                'maxOutputTokens' => 1024, // [AI Narrator]
                'temperature'     => 0.7, // [AI Narrator]
            ], // [AI Narrator]
        ]; // [AI Narrator]

        try { // [AI Narrator]
            // [AI Narrator] withoutVerifying() fixes cURL SSL error 60 on Windows local dev
            // Remove this in production and configure proper SSL certificates instead
            $response = Http::withoutVerifying()->timeout(10) // [AI Narrator]
                ->post($url, $body); // [AI Narrator]

            if (! $response->successful()) { // [AI Narrator]
                return $this->fallback($response->status(), $response->body()); // [AI Narrator]
            } // [AI Narrator]

            $text = $response->json('candidates.0.content.parts.0.text'); // [AI Narrator]

            return $text ?? $this->fallback($response->status(), $response->body()); // [AI Narrator]
        } catch (Throwable $e) { // [AI Narrator]
            Log::error('[AI Narrator] Gemini API exception', ['error' => $e->getMessage()]); // [AI Narrator]

            return $this->fallback(); // [AI Narrator]
        } // [AI Narrator]
    }

    private function buildContextText(array $data): string // [AI Narrator]
    {
        $totalCollected = number_format((float) ($data['total_collected'] ?? 0), 2); // [AI Narrator]
        $cashAmount     = number_format((float) ($data['cash_amount']     ?? 0), 2); // [AI Narrator]
        $gcashAmount    = number_format((float) ($data['gcash_amount']    ?? 0), 2); // [AI Narrator]

        return implode("\n", [ // [AI Narrator]
            'Organization: ' . ($data['org_name'] ?? 'N/A') . ' (' . ($data['org_type'] ?? 'N/A') . ')', // [AI Narrator]
            'Semester: '     . ($data['semester'] ?? 'N/A'), // [AI Narrator]
            'Total Collected: ₱' . $totalCollected, // [AI Narrator]
            'Transactions Today: '  . ($data['today_count']    ?? 0), // [AI Narrator]
            'Enrolled Students: '   . ($data['enrolled_count'] ?? 0), // [AI Narrator]
            'Fee Transactions: '    . ($data['fee_count']      ?? 0) . ' | Fine Transactions: ' . ($data['fine_count'] ?? 0), // [AI Narrator]
            'Cash Collected: ₱'    . $cashAmount, // [AI Narrator]
            'GCash Collected: ₱'   . $gcashAmount, // [AI Narrator]
            'Unremitted Transactions: ' . ($data['unremitted_count']   ?? 0), // [AI Narrator]
            'Pending Void Requests: '   . ($data['pending_void_count'] ?? 0), // [AI Narrator]
        ]); // [AI Narrator]
    }

    private function systemInstruction(): string // [AI Narrator]
    {
        return <<<'PROMPT'
You are a financial reporting assistant for a Philippine university student council system.
Write a professional 3-paragraph financial narrative based on the data provided.

Paragraph 1 — Collection Performance:
  Summarize total collected, transaction volume, and overall status.

Paragraph 2 — Payment Trends:
  Analyze Cash vs GCash split and what it means for remittance handling.

Paragraph 3 — Action Items:
  Flag unremitted transactions, pending voids, or unpaid students.
  Give 1-2 specific actionable recommendations.

Rules:
- Use ₱ for peso amounts with comma formatting (e.g. ₱12,400.00)
- Be concise — max 180 words total
- Professional but readable tone
- If no issues found, say so positively
- Never fabricate numbers — only use data provided
- Do not include any preamble like "Here is..." or "Below is..."
- Start directly with the first paragraph of the narrative
PROMPT; // [AI Narrator]
    }

    private function fallback(?int $status = null, ?string $body = null): string // [AI Narrator]
    {
        Log::error('[AI Narrator] Gemini API failed', ['status' => $status, 'body' => $body]); // [AI Narrator]

        return 'AI narrative unavailable at this time. Please check your collection summary above.'; // [AI Narrator]
    }
}
