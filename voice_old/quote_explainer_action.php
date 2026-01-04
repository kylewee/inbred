<?php
/**
 * Quote Explainer Action Handler
 * When customer presses 1 to talk to Kyle
 */

header('Content-Type: text/xml');

$digit = $_POST['Digits'] ?? '';

echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($digit === '1') {
    // Transfer to Kyle's cell
    ?>
    <Response>
        <Say voice="Polly.Joanna">Great! Connecting you to Kyle now. Please hold.</Say>
        <Dial timeout="20" callerId="+19047066669">
            <Number>+19046634789</Number>
        </Dial>
        <Say voice="Polly.Joanna">Sorry, Kyle is unavailable right now. Please call back at 904-706-6669 or approve your quote via text message.</Say>
    </Response>
    <?php
} else {
    ?>
    <Response>
        <Say voice="Polly.Joanna">Thanks for listening! Approve your quote anytime by tapping the link in your text message. Have a great day!</Say>
    </Response>
    <?php
}
