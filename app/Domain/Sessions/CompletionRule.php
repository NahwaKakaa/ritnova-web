<?php

namespace App\Domain\Sessions;

/**
 * Status "selesai" ditetapkan oleh pemenuhan seluruh aktivitas wajib,
 * bukan oleh tombol centang manual atau timer pasif.
 */
final class CompletionRule
{
    public function __construct(
        private float $mediaThreshold = 0.95,
        private bool $requireQuiz = true,
        private bool $requireAction = true,
    ) {}

    /**
     * @return list<string>  daftar syarat yang belum terpenuhi (kosong = boleh selesai)
     */
    public function missing(float $mediaCompletionPct, bool $quizDone, bool $actionDone): array
    {
        $missing = [];

        if ($mediaCompletionPct < $this->mediaThreshold) {
            $missing[] = 'media';
        }

        if ($this->requireQuiz && ! $quizDone) {
            $missing[] = 'quiz';
        }

        if ($this->requireAction && ! $actionDone) {
            $missing[] = 'action';
        }

        return $missing;
    }

    public function canComplete(float $mediaCompletionPct, bool $quizDone, bool $actionDone): bool
    {
        return $this->missing($mediaCompletionPct, $quizDone, $actionDone) === [];
    }
}
