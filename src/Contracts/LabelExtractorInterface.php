<?php
declare(strict_types=1);

namespace DonTeeWhy\LogStack\Contracts;

/**
 * Interface for dynamic label extractors.
 * 
 * Allows extraction of contextual labels from the current request,
 * authentication state, or other runtime information.
 */
interface LabelExtractorInterface
{
    /**
     * Extract labels from the current context.
     * 
     * @return array Key-value pairs of labels to add to log entries
     */
    public function extract(): array;

    /**
     * Get the priority of this extractor.
     * 
     * Higher priority extractors run later and can override labels
     * from lower priority extractors.
     * 
     * @return int Priority value (higher = later execution)
     */
    public function getPriority(): int;

    /**
     * Check if this extractor should run in the current context.
     * 
     * @return bool True if extractor should run
     */
    public function shouldRun(): bool;
}
