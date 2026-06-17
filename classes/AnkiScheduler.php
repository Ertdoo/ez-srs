<?php
class AnkiScheduler
{
    // Added int types to clear warnings
    private static function applyFuzz(int $ivl): int
    {
        if ($ivl < 2) {
            return $ivl;
        }
        if ($ivl === 2) {
            // Changed to strict ===
            return $ivl + rand(-1, 1);
        }
        $fuzz = 0;
        if ($ivl < 7) {
            $fuzz = (int) round($ivl * 0.25);
        } elseif ($ivl < 30) {
            $fuzz = max(2, (int) round($ivl * 0.15));
        } else {
            $fuzz = max(4, (int) round($ivl * 0.05));
        }
        return max(1, $ivl + rand(-$fuzz, $fuzz));
    }

    // Explicitly declaring parameter types and return type
    public static function processAnswer(
        bool $isNew,
        float $ease,
        int $interval,
        ?string $nextDue,
        ?string $learningDueAt,
        string $outcome,
    ): array {
        $now = time();
        $today = strtotime("today");

        $newEase = $isNew ? 2.5 : $ease;
        $newInterval = $isNew ? 0 : $interval;
        $newNextDue = null;
        $newLearningDue = null;

        $delayDays = 0;
        if (!$isNew && $nextDue !== null && $nextDue !== "0000-00-00") {
            $delayDays = max(
                0,
                (int) floor(($today - strtotime($nextDue)) / 86400),
            );
        }

        $inLearning = $isNew || $learningDueAt !== null;
        $step = $newInterval === 0 ? 0 : 1;

        if ($inLearning) {
            switch ($outcome) {
                case "again":
                    $newInterval = 0;
                    $newLearningDue = date(
                        "Y-m-d H:i:s",
                        strtotime("+1 minute"),
                    );
                    break;
                case "hard":
                    $newInterval = $step === 0 ? 0 : 1;
                    $delay = $step === 0 ? "+6 minutes" : "+10 minutes";
                    $newLearningDue = date("Y-m-d H:i:s", strtotime($delay));
                    break;
                case "good":
                    if ($step === 0) {
                        $newInterval = 1;
                        $newLearningDue = date(
                            "Y-m-d H:i:s",
                            strtotime("+10 minutes"),
                        );
                    } else {
                        $newInterval = 1;
                        $newNextDue = date("Y-m-d", strtotime("+1 day"));
                    }
                    break;
                case "easy":
                    $newInterval = 4;
                    $newNextDue = date("Y-m-d", strtotime("+4 days"));
                    break;
            }
        } else {
            $hardIvl = max((int) round($interval * 1.2), $interval + 1, 1);
            $goodIvl = max(
                (int) round(($interval + intdiv($delayDays, 2)) * $ease),
                $hardIvl + 1,
                1,
            );
            $easyIvl = max(
                (int) round(($interval + $delayDays) * $ease * 1.3),
                $goodIvl + 1,
                1,
            );

            switch ($outcome) {
                case "again":
                    $newEase = max(1.3, $ease - 0.2);
                    $newInterval = 1;
                    $newLearningDue = date(
                        "Y-m-d H:i:s",
                        strtotime("+10 minutes"),
                    );
                    break;
                case "hard":
                    $newEase = max(1.3, $ease - 0.15);
                    $newInterval = self::applyFuzz($hardIvl);
                    $newNextDue = date(
                        "Y-m-d",
                        strtotime("+{$newInterval} days"),
                    );
                    break;
                case "good":
                    $newInterval = self::applyFuzz($goodIvl);
                    $newNextDue = date(
                        "Y-m-d",
                        strtotime("+{$newInterval} days"),
                    );
                    break;
                case "easy":
                    $newEase = min(4.0, $ease + 0.15);
                    $newInterval = self::applyFuzz($easyIvl);
                    $newNextDue = date(
                        "Y-m-d",
                        strtotime("+{$newInterval} days"),
                    );
                    break;
            }
        }

        if ($newNextDue === null) {
            $newNextDue = date("Y-m-d");
        }

        return [
            "ease_factor" => $newEase,
            "interval_days" => $newInterval,
            "next_due_date" => $newNextDue,
            "learning_due_at" => $newLearningDue,
        ];
    }
}
?>
