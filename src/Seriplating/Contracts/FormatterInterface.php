<?php

namespace Prewk\Seriplating\Contracts;

/**
 * Formats a serialization or imports a serialization in a specific format
 */
interface FormatterInterface
{
    /**
     * Format a serialization
     *
     * @param array $serialized Serialization
     * @return string Formatted serialization
     */
    public function formatSerialized(array $serialized);

    /**
     * Convert a formatted serialization
     *
     * @param string $formatted Formatted serialization
     * @return void Serialization
     */
    public function unformatSerialized($formatted);
}