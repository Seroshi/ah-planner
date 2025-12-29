<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

trait HandlesDatabaseFailures
{
    /**
     * Safely execute a database query with a fallback.
     */
    public function safeQuery(callable $query, $fallback = [])
    {
        try {
            // Optional: Quick check if connection is even possible
            // DB::connection()->getPdo(); 
            return $query();
            
        } catch (\Exception $e) {
            // Log the actual error so you can fix it later
            Log::error("Database connection failed: " . $e->getMessage());
            
            // Return the safe fallback data
            return $fallback;
        }
    }
}