<?php
/**
 * Call Tracking Hook for Voice System
 *
 * Integrates phone calls with A/B testing attribution.
 * Include this file from recording_callback.php after lead creation.
 *
 * Usage in recording_callback.php:
 *   require_once __DIR__ . '/call_tracking_hook.php';
 *   $abResult = track_call_for_ab($callSid, $from, $to, $duration, $leadId, $wasAnswered);
 *
 * CRM Integration:
 *   When creating leads, call get_ab_attribution_for_crm() to get source field value
 */

require_once __DIR__ . '/../lib/CallTracking.php';

/**
 * Get A/B attribution data formatted for CRM source field
 * Call this BEFORE creating a lead to get the source value
 *
 * @param string $callerPhone - The caller's phone number
 * @return array - ['source' => 'formatted source string', 'experiment' => '...', 'variant' => '...']
 */
function get_ab_attribution_for_crm(string $callerPhone): array {
    try {
        $tracker = new CallTracking();

        // Use the internal attribution finder
        $reflection = new ReflectionClass($tracker);
        $method = $reflection->getMethod('findCallAttribution');
        $method->setAccessible(true);

        $normalizeMethod = $reflection->getMethod('normalizePhone');
        $normalizeMethod->setAccessible(true);
        $normalizedPhone = $normalizeMethod->invoke($tracker, $callerPhone);

        $attribution = $method->invoke($tracker, $normalizedPhone);

        $source = 'Phone Call';
        if (!empty($attribution['ab_experiment']) && !empty($attribution['ab_variant'])) {
            $source = "Phone Call | A/B: {$attribution['ab_experiment']} / Variant {$attribution['ab_variant']}";
        }
        if (!empty($attribution['utm_source'])) {
            $source .= " | UTM: {$attribution['utm_source']}";
        }
        if (!empty($attribution['source_page'])) {
            $source .= " | Page: {$attribution['source_page']}";
        }

        return [
            'source' => $source,
            'experiment' => $attribution['ab_experiment'] ?? '',
            'variant' => $attribution['ab_variant'] ?? '',
            'method' => $attribution['method'] ?? 'none'
        ];
    } catch (Exception $e) {
        error_log("get_ab_attribution_for_crm error: " . $e->getMessage());
        return ['source' => 'Phone Call', 'experiment' => '', 'variant' => '', 'method' => 'error'];
    }
}

/**
 * Track a phone call for A/B testing attribution
 *
 * @param string $callSid - SignalWire/Twilio call SID
 * @param string $from - Caller phone number
 * @param string $to - Called phone number (business number)
 * @param int $duration - Call duration in seconds
 * @param int|null $leadId - CRM lead ID if created
 * @param bool $wasAnswered - Whether the call was answered
 * @param string|null $recordingUrl - URL to call recording
 * @param string|null $transcription - Call transcription
 * @return array - Tracking result with attribution info
 */
function track_call_for_ab(
    string $callSid,
    string $from,
    string $to,
    int $duration = 0,
    ?int $leadId = null,
    bool $wasAnswered = false,
    ?string $recordingUrl = null,
    ?string $transcription = null
): array {
    try {
        $tracker = new CallTracking();

        // First, track the incoming call to get attribution
        $callResult = $tracker->trackIncomingCall([
            'CallSid' => $callSid,
            'From' => $from,
            'To' => $to,
            'CallStatus' => $wasAnswered ? 'completed' : 'no-answer'
        ]);

        // Then update with completion data
        $updateData = [
            'call_status' => $wasAnswered ? 'completed' : 'no-answer',
            'call_duration' => $duration,
            'was_answered' => $wasAnswered,
        ];

        if ($recordingUrl) {
            $updateData['recording_url'] = $recordingUrl;
        }
        if ($transcription) {
            $updateData['transcription'] = substr($transcription, 0, 1000); // Limit size
        }
        if ($leadId) {
            $updateData['lead_created'] = true;
            $updateData['lead_id'] = $leadId;
        }

        $tracker->updateCall($callSid, $updateData);

        // Log the result for debugging
        $logEntry = [
            'ts' => date('c'),
            'event' => 'ab_call_tracked',
            'call_sid' => $callSid,
            'call_id' => $callResult['call_id'],
            'attributed' => $callResult['attributed'],
            'ab_experiment' => $callResult['ab_experiment'],
            'ab_variant' => $callResult['ab_variant'],
            'attribution_method' => $callResult['attribution_method'],
            'lead_id' => $leadId
        ];
        @file_put_contents(__DIR__ . '/call_tracking.log', json_encode($logEntry) . "\n", FILE_APPEND);

        return $callResult;

    } catch (Exception $e) {
        error_log("CallTracking hook error: " . $e->getMessage());
        return [
            'error' => $e->getMessage(),
            'attributed' => false
        ];
    }
}

/**
 * Track a missed call for A/B testing
 *
 * @param string $callSid
 * @param string $from
 * @param string $to
 * @return array
 */
function track_missed_call_for_ab(string $callSid, string $from, string $to): array {
    return track_call_for_ab($callSid, $from, $to, 0, null, false);
}

/**
 * Get A/B call statistics for dashboard
 *
 * @param string|null $experimentName - Filter by experiment name
 * @return array
 */
function get_ab_call_stats(?string $experimentName = null): array {
    try {
        $tracker = new CallTracking();
        return $tracker->getABCallStats($experimentName);
    } catch (Exception $e) {
        error_log("CallTracking stats error: " . $e->getMessage());
        return [];
    }
}
