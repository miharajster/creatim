<?php

class Whitelist
{
    /**
     * Apply whitelist to filter columns in data
     * @param array $data Single item or array of items
     * @param array $whitelist List of allowed column names
     * @return array Filtered data
     */
    public static function apply(array $data, array $whitelist): array
    {
        if (empty($data)) {
            return $data;
        }
        
        // Check if it's a single item or array of items
        if (isset($data[0]) && is_array($data[0])) {
            // Multiple items
            return array_map(fn($item) => array_intersect_key($item, array_flip($whitelist)), $data);
        }
        
        // Single item
        return array_intersect_key($data, array_flip($whitelist));
    }
}
