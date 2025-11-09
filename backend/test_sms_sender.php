<?php

require_once __DIR__ . '/lib/Sms.php';
require_once __DIR__ . '/lib/PdoConnector.php';

header('Content-Type: text/plain');

$sms = new Sms();
$unsentMessages = $sms->getUnsentSMS();

if (empty($unsentMessages)) {
    echo "No unsent SMS messages found.\n";
    exit;
}

echo "Processing " . count($unsentMessages) . " unsent SMS messages...\n\n";

$index = 0;
while ($index < count($unsentMessages)) {
    $message = $unsentMessages[$index];
    $phone = $message['customer_phone'];
    $content = $message['content'];
    $created = $message['created'];
    
    // Check if the row 5 positions before exists
    $fifthBeforeIndex = $index - 5;
    
    if ($fifthBeforeIndex >= 0) {
        // Get the message that was 5 rows before current
        $fifthBeforeMessage = $unsentMessages[$fifthBeforeIndex];
        $fifthBeforeCreated = $fifthBeforeMessage['created'];
        
        // Calculate time difference
        $currentTime = strtotime($created);
        $fifthBeforeTime = strtotime($fifthBeforeCreated);
        $timeDifferenceInSeconds = $currentTime - $fifthBeforeTime;
        $timeDifferenceInHours = $timeDifferenceInSeconds / 3600;
        
        // If the 5th row before is younger than 1 hour (within 1 hour)
        if ($timeDifferenceInHours < 1) {
            echo "$phone sent from PAYED (created: $created, 5th before: $fifthBeforeCreated, diff: " . round($timeDifferenceInHours, 4) . " hours)\n";
        } else {
            echo "$phone sent from FREE (created: $created, 5th before: $fifthBeforeCreated, diff: " . round($timeDifferenceInHours, 4) . " hours)\n";
        }
    } else {
        // Less than 5 messages, use free service
        echo "$phone sent from FREE (only " . ($index + 1) . " messages so far)\n";
    }
    
    // Mark SMS as sent
    $sms->markAsSent($message['id']);
    
    $index++;
}

echo "\nProcessing complete.\n";
