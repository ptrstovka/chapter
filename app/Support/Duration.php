<?php


namespace App\Support;


class Duration
{
    public function __construct(
        protected int $seconds
    ) { }

    /**
     * Format numeric value to a string integer.
     */
    protected function formatNumber(int|float $value): string
    {
        return number_format($value, 0, '', '');
    }

    /**
     * Format duration to duration string.
     */
    public function format(): string
    {
        $hours = floor($this->seconds / 3600);
        $minutes = floor(($this->seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$this->formatNumber($hours)}h {$this->formatNumber($minutes)}m";
        }

        return "{$this->formatNumber($minutes)} min";
    }

    /**
     * Create new duration instance for given amount of seconds.
     */
    public static function seconds(int $seconds): static
    {
        return new static($seconds);
    }
}
