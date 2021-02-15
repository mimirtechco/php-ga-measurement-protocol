<?php

require '../vendor/autoload.php';

use TheIconic\Tracking\GoogleAnalytics\Analytics;

// Instantiate the Analytics object
// optionally pass TRUE in the constructor if you want to connect using HTTPS
$analytics = new Analytics(true);

// Build the GA hit using the Analytics class methods
// they should Autocomplete if you use a PHP IDE
$analytics
    ->setProtocolVersion('1')
    ->setTrackingId('UA-26293728-11')
    ->setClientId('12345678')
    ->setDocumentPath('/mypage')
    ->setIpOverride("202.126.106.175");

// When you finish bulding the payload send a hit (such as an pageview or event)

$response = $analytics->setDebug(true)->sendPageview();

$debugResposne = $response->getDebugResponse();
print_r($debugResposne);