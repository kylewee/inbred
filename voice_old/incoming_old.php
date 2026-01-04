<?php
// SignalWire SWML response for incoming calls
// Direct dial to cell phone - NO GREETING

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$swml = [
    "version" => "1.0.0",
    "sections" => [
        "main" => [
            [
                "dial" => [
                    "to" => "+19046634789",
                    "timeout" => 30,
                    "record" => true,
                    "recording_status_callback" => "https://mechanicstaugustine.com/voice/recording_callback.php?t=" . time()
                ]
            ]
        ]
    ]
];

echo json_encode($swml);
?>