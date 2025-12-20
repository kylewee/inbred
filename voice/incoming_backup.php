<?php
// SignalWire SWML response for incoming calls
// Direct dial to cell phone without greeting

$swml = [
    "version" => "1.0.0",
    "sections" => [
        "main" => [
            [
                "dial" => [
                    "to" => "+19046634789",
                    "timeout" => 30,
                    "record" => true,
                    "recording_status_callback" => "https://mechanicstaugustine.com/voice/recording_callback.php"
                ]
            ]
        ]
    ]
];

header('Content-Type: application/json');
echo json_encode($swml, JSON_PRETTY_PRINT);
?>