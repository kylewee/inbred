<?php
/**
 * IVR Recording Processor
 * Transcribes each answer and creates a CRM lead when complete
 */
declare(strict_types=1);

// Load environment
$envPath = __DIR__ . '/../api/.env.local.php';
if (file_exists($envPath) && !defined('SIGNALWIRE_PROJECT_ID')) {
    require_once $envPath;
}

// Load helpers
define('VOICE_LIB_ONLY', true);
require_once __DIR__ . '/recording_callback.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$callSid = $_REQUEST['call_sid'] ?? $_REQUEST['CallSid'] ?? '';
$step = (int)($_REQUEST['step'] ?? -1);
$recordingUrl = $_REQUEST['RecordingUrl'] ?? '';
$recordingSid = $_REQUEST['RecordingSid'] ?? '';

// Session directory
$sessionDir = __DIR__ . '/ivr_sessions';
$sessionFile = $sessionDir . '/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';

// Log event
$log = [
    'ts' => date('c'),
    'event' => 'ivr_recording_callback',
    'action' => $action,
    'call_sid' => $callSid,
    'step' => $step,
    'recording_url' => $recordingUrl,
    'recording_sid' => $recordingSid
];
@file_put_contents(__DIR__ . '/ivr_recording.log', json_encode($log) . "\n", FILE_APPEND);

// Step labels for field mapping
$stepLabels = ['name', 'year', 'make', 'model', 'engine', 'problem'];

// Handle individual recording callback (per-step)
if ($recordingUrl && $step >= 0 && $step < count($stepLabels)) {
    $label = $stepLabels[$step];
    
    // Load session
    $session = [];
    if (file_exists($sessionFile)) {
        $session = json_decode(file_get_contents($sessionFile), true) ?: [];
    }
    
    // Store recording
    $session['recordings'][$label] = $recordingUrl;
    $session['recording_sids'][$label] = $recordingSid;
    
    // Try to transcribe immediately
    if (defined('OPENAI_API_KEY') && OPENAI_API_KEY && $recordingSid) {
        $dl = fetch_signalwire_recording_mp3($recordingSid);
        if (!empty($dl['ok'])) {
            $wx = whisper_transcribe_bytes((string)$dl['data'], $recordingSid . '.mp3');
            if (!empty($wx['ok'])) {
                $session['transcripts'][$label] = trim($wx['text']);
                $log['transcript'] = $session['transcripts'][$label];
            }
        }
    }
    
    $session['updated'] = date('c');
    file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    
    @file_put_contents(__DIR__ . '/ivr_recording.log', json_encode(['stored' => $label, 'session' => $session]) . "\n", FILE_APPEND);
    
    echo json_encode(['ok' => true, 'step' => $step, 'label' => $label]);
    exit;
}

// Handle full session processing
if ($action === 'process') {
    if (!file_exists($sessionFile)) {
        echo json_encode(['ok' => false, 'error' => 'Session not found']);
        exit;
    }
    
    $session = json_decode(file_get_contents($sessionFile), true);
    if (!$session) {
        echo json_encode(['ok' => false, 'error' => 'Invalid session data']);
        exit;
    }
    
    // If transcripts are missing, try to transcribe now
    if (empty($session['transcripts']) && !empty($session['recording_sids'])) {
        $session['transcripts'] = [];
        foreach ($session['recording_sids'] as $label => $sid) {
            if (defined('OPENAI_API_KEY') && OPENAI_API_KEY) {
                $dl = fetch_signalwire_recording_mp3($sid);
                if (!empty($dl['ok'])) {
                    $wx = whisper_transcribe_bytes((string)$dl['data'], $sid . '.mp3');
                    if (!empty($wx['ok'])) {
                        $session['transcripts'][$label] = trim($wx['text']);
                    }
                }
            }
        }
        file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    }
    
    $transcripts = $session['transcripts'] ?? [];
    
    // Build lead data
    $name = $transcripts['name'] ?? '';
    $nameParts = explode(' ', $name, 2);
    
    $leadData = [
        'phone' => $session['from'] ?? '',
        'first_name' => $nameParts[0] ?? $name,
        'last_name' => $nameParts[1] ?? '',
        'name' => $name,
        'year' => $transcripts['year'] ?? '',
        'make' => $transcripts['make'] ?? '',
        'model' => $transcripts['model'] ?? '',
        'engine' => $transcripts['engine'] ?? '',
        'notes' => $transcripts['problem'] ?? '',
        'source' => 'IVR',
        'transcript' => build_full_transcript($transcripts),
        'created_at' => date('c'),
    ];
    
    // Clean up year (extract 4-digit number)
    if (!empty($leadData['year']) && preg_match('/\b(19|20)\d{2}\b/', $leadData['year'], $m)) {
        $leadData['year'] = $m[0];
    }
    
    // Clean up engine (handle "skip" responses)
    if (!empty($leadData['engine']) && stripos($leadData['engine'], 'skip') !== false) {
        $leadData['engine'] = '';
    }
    
    // Create CRM lead
    $crmResult = create_crm_lead($leadData);
    
    // Generate estimate
    if (!empty($leadData['notes'])) {
        require_once __DIR__ . '/../scraper/auto_estimate.php';
        $estimate = auto_estimate_from_transcript(
            $leadData['notes'],
            $leadData['year'],
            $leadData['make'],
            $leadData['model']
        );
        
        if (!empty($estimate['success'])) {
            // Send approval SMS to mechanic
            $smsResult = send_estimate_approval_sms($estimate, $leadData);
            $session['estimate'] = $estimate;
            $session['sms_result'] = $smsResult;
        }
    }
    
    $session['lead_data'] = $leadData;
    $session['crm_result'] = $crmResult;
    $session['processed'] = true;
    $session['processed_at'] = date('c');
    file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    
    @file_put_contents(__DIR__ . '/ivr_recording.log', json_encode(['processed' => $session]) . "\n", FILE_APPEND);
    
    echo json_encode(['ok' => true, 'lead' => $leadData, 'crm' => $crmResult]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);

// Helper to build full transcript from parts
function build_full_transcript(array $transcripts): string {
    $parts = [];
    $labels = [
        'name' => 'Name',
        'year' => 'Year',
        'make' => 'Make',
        'model' => 'Model',
        'engine' => 'Engine',
        'problem' => 'Problem'
    ];
    
    foreach ($labels as $key => $label) {
        if (!empty($transcripts[$key])) {
            $parts[] = "{$label}: {$transcripts[$key]}";
        }
    }
    
    return implode("\n", $parts);
}

// Send estimate approval SMS to mechanic
function send_estimate_approval_sms(array $estimate, array $leadData): array {
    if (!defined('SIGNALWIRE_PROJECT_ID') || !defined('SIGNALWIRE_API_TOKEN') || !defined('SIGNALWIRE_SPACE')) {
        return ['ok' => false, 'error' => 'SignalWire not configured'];
    }
    
    $mechanicNumber = defined('MECHANIC_CELL_NUMBER') ? MECHANIC_CELL_NUMBER : '';
    $fromNumber = defined('SIGNALWIRE_PHONE_NUMBER') ? SIGNALWIRE_PHONE_NUMBER : '';
    
    if (!$mechanicNumber || !$fromNumber) {
        return ['ok' => false, 'error' => 'Phone numbers not configured'];
    }
    
    // Store pending estimate
    $pendingFile = __DIR__ . '/pending_estimates.json';
    $pending = [];
    if (file_exists($pendingFile)) {
        $pending = json_decode(file_get_contents($pendingFile), true) ?: [];
    }
    
    $pendingId = uniqid('est_');
    $pending[$pendingId] = [
        'id' => $pendingId,
        'estimate' => $estimate,
        'lead' => $leadData,
        'created_at' => date('c'),
        'expires_at' => date('c', strtotime('+24 hours'))
    ];
    file_put_contents($pendingFile, json_encode($pending, JSON_PRETTY_PRINT));
    
    // Build message
    $vehicle = "{$leadData['year']} {$leadData['make']} {$leadData['model']}";
    $customerPhone = $leadData['phone'] ?? 'Unknown';
    $total = number_format($estimate['grand_total'], 2);
    
    $repairs = [];
    foreach ($estimate['estimates'] as $est) {
        $repairs[] = $est['repair'];
    }
    
    $msg = "NEW ESTIMATE REQUEST\n";
    $msg .= "Customer: {$leadData['name']}\n";
    $msg .= "Phone: {$customerPhone}\n";
    $msg .= "Vehicle: {$vehicle}\n";
    $msg .= "Repairs: " . implode(', ', $repairs) . "\n";
    $msg .= "Total: \${$total}\n\n";
    $msg .= "Reply YES to send estimate to customer.\n";
    $msg .= "Reply NO to decline.\n";
    $msg .= "(ID: {$pendingId})";
    
    return send_signalwire_sms($fromNumber, $mechanicNumber, $msg);
}

// Send SMS via SignalWire
function send_signalwire_sms(string $from, string $to, string $message): array {
    $projectId = SIGNALWIRE_PROJECT_ID;
    $apiToken = SIGNALWIRE_API_TOKEN;
    $space = SIGNALWIRE_SPACE;
    
    $url = "https://{$space}/api/laml/2010-04-01/Accounts/{$projectId}/Messages.json";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ]),
        CURLOPT_USERPWD => "{$projectId}:{$apiToken}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    
    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'ok' => ($http >= 200 && $http < 300),
        'http' => $http,
        'error' => $error,
        'response' => json_decode($resp, true)
    ];
}
