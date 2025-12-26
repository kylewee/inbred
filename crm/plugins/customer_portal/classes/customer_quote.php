<?php
/**
 * Customer Quote Class
 * Handles database queries for customer quote lookups
 */

class customer_quote {

    /**
     * Find customer lead by phone number
     * @param string $phone Phone number (cleaned)
     * @return array|null Lead data or null if not found
     */
    public static function find_by_phone($phone) {
        // Clean phone number - keep only digits
        $phone_clean = preg_replace('/[^\d]/', '', $phone);

        if (strlen($phone_clean) < 4) {
            return null;
        }

        // Search for phone number in CRM (last 4 digits match or full match)
        $query = db_query("
            SELECT
                id,
                field_219 as first_name,
                field_220 as last_name,
                field_227 as phone,
                field_228 as stage,
                field_229 as source,
                field_230 as notes,
                field_231 as year,
                field_232 as make,
                field_233 as model,
                field_234 as address,
                field_235 as email,
                date_added,
                created_by
            FROM app_entity_26
            WHERE field_227 LIKE '%" . db_input($phone_clean) . "%'
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($lead = db_fetch_array($query)) {
            return $lead;
        }

        return null;
    }

    /**
     * Get lead by ID
     * @param int $lead_id Lead ID
     * @return array|null Lead data or null if not found
     */
    public static function get_by_id($lead_id) {
        $lead_id = (int)$lead_id;

        $query = db_query("
            SELECT
                id,
                field_219 as first_name,
                field_220 as last_name,
                field_227 as phone,
                field_228 as stage,
                field_229 as source,
                field_230 as notes,
                field_231 as year,
                field_232 as make,
                field_233 as model,
                field_234 as address,
                field_235 as email,
                date_added,
                created_by
            FROM app_entity_26
            WHERE id = {$lead_id}
        ");

        if ($lead = db_fetch_array($query)) {
            return $lead;
        }

        return null;
    }

    /**
     * Parse auto-estimate from notes field
     * @param string $notes Notes field content
     * @return array|null Estimate data or null if not found
     */
    public static function parse_estimate($notes) {
        // Look for estimate data in notes
        // Format: "Estimate: {json}" or just JSON

        if (empty($notes)) {
            return null;
        }

        // Try to find JSON in notes
        if (preg_match('/\{[^}]*"estimates"[^}]*\}/s', $notes, $matches)) {
            $estimate_data = json_decode($matches[0], true);
            if ($estimate_data && isset($estimate_data['estimates'])) {
                return $estimate_data;
            }
        }

        // Alternative: Look for structured estimate data
        // "Labor: $XXX, Parts: $YYY, Total: $ZZZ"
        $labor = 0;
        $parts = 0;
        $total = 0;

        if (preg_match('/Labor:?\s*\$?(\d+\.?\d*)/i', $notes, $m)) {
            $labor = floatval($m[1]);
        }
        if (preg_match('/Parts:?\s*\$?(\d+\.?\d*)/i', $notes, $m)) {
            $parts = floatval($m[1]);
        }
        if (preg_match('/Total:?\s*\$?(\d+\.?\d*)/i', $notes, $m)) {
            $total = floatval($m[1]);
        }

        if ($total > 0 || $labor > 0 || $parts > 0) {
            return [
                'labor_cost' => $labor,
                'parts_cost' => $parts,
                'total' => $total > 0 ? $total : ($labor + $parts)
            ];
        }

        return null;
    }

    /**
     * Update quote approval status
     * @param int $lead_id Lead ID
     * @param string $status 'approved' or 'declined'
     * @return bool Success
     */
    public static function update_approval_status($lead_id, $status) {
        global $app_user;

        $lead_id = (int)$lead_id;
        $approved = ($status === 'approved') ? 1 : 0;

        // Update quote_approved field (add column if doesn't exist)
        $result = db_query("
            UPDATE app_entity_26
            SET
                field_228 = '" . db_input($status === 'approved' ? 'Approved' : 'Declined') . "'
            WHERE id = {$lead_id}
        ");

        // Log the action in notes
        $timestamp = date('Y-m-d H:i:s');
        $action_log = "\n\n--- Customer Portal Action ---\n";
        $action_log .= "Status: " . ucfirst($status) . "\n";
        $action_log .= "Date: {$timestamp}\n";
        $action_log .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

        db_query("
            UPDATE app_entity_26
            SET field_230 = CONCAT(field_230, '" . db_input($action_log) . "')
            WHERE id = {$lead_id}
        ");

        return $result !== false;
    }

    /**
     * Get dispatch job for lead (if exists)
     * @param int $lead_id Lead ID
     * @return array|null Job data or null
     */
    public static function get_dispatch_job($lead_id) {
        $lead_id = (int)$lead_id;

        $query = db_query("
            SELECT *
            FROM dispatch_jobs
            WHERE lead_id = {$lead_id}
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($job = db_fetch_array($query)) {
            return $job;
        }

        return null;
    }

    /**
     * Format phone number for display
     * @param string $phone Phone number
     * @return string Formatted phone
     */
    public static function format_phone($phone) {
        $clean = preg_replace('/[^\d]/', '', $phone);

        if (strlen($clean) === 10) {
            return sprintf('(%s) %s-%s',
                substr($clean, 0, 3),
                substr($clean, 3, 3),
                substr($clean, 6, 4)
            );
        }

        return $phone;
    }
}
