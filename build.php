<?php

require_once './functions.php';

$jobUrl = base64_decode($argv[1]);
$jobName = base64_decode($argv[2]);
$buildNumber = $argv[3];

logTable([
    'jobUrl' => $jobUrl,
    'jobName' => $jobName,
    'buildNumber' => $buildNumber,
], 'Starting APK Build');

// Download archive
exec("wget $jobUrl -O ./sources.zip");
exec("unzip ./sources.zip -d ./sources");
exec("./build.sh");