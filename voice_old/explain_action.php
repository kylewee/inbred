<?php
/**
 * AI Explainer Action - Transfer to Kyle
 */

header('Content-Type: text/xml');

$digit = $_POST['Digits'] ?? '';

echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($digit === '1') {
    ?>
<Response>
    <Say voice="Polly.Matthew">Connecting you to Kyle now.</Say>
    <Dial timeout="25" callerId="+19047066669">
        <Number>+19046634789</Number>
    </Dial>
    <Say voice="Polly.Matthew">Kyle is unavailable. Please call back at 904-706-6669 or approve via text.</Say>
</Response>
    <?php
} else {
    ?>
<Response>
    <Say voice="Polly.Matthew">Thanks! Approve your quote anytime via the text message link.</Say>
</Response>
    <?php
}
