<?php

namespace Cli;

require_once './lib/Colors.php';
require_once './lib/Utils.php';

try {
    $jobUrl = base64_decode($argv[1]);
    $jobName = base64_decode($argv[2]);
    $license = base64_decode($argv[3]);
    $appId = base64_decode($argv[4]);
    $buildNumber = $argv[5];

    Utils::logTable([
        'jobUrl' => $jobUrl,
        'jobName' => $jobName,
        'license' => $license,
        'appId' => $appId,
        'buildNumber' => $buildNumber,
    ], 'Starting APK Build');

    // Test licence ("MAE Hosted", "PE Hosted") are only allowed!
    if (is_file('./exclude.json')) {
        $exceptions = json_decode(file_get_contents('./exclude.json'), true);
        if (in_array($license, $exceptions)) {
            Utils::log("You are not allowed to use this service.", "error");
            exit(1);
        }
    }

    $licenseType = Utils::checkLicense($license);
    if ($licenseType != 0 || array_key_exists('error', $licenseType)) {
        Utils::log("An error occurred while checking the license.", "error");
        exit(1);
    }

    Utils::log("Type is {$licenseType}", "info");

    switch (strtolower($licenseType))
    {
        case 'mae hosted':
        case 'pe hosted':
            // Ok!
            break;
        default:
            Utils::log("You are not allowed to use this service.", "error");
            exit(1);
    }

    // Download archive
    Utils::log("Downloading {$jobUrl}", "info");
    exec("rm -Rf ./sources ./sources.zip");
    exec("rm -Rf ./*.apk");
    exec("wget --quiet $jobUrl -O ./sources.zip",$o, $return);
    if ($return != 0) {
        Utils::log("An error occurred while download the archive {$jobUrl}", "error");
        exit(1);
    }
    exec("unzip ./sources.zip -d ./sources");
    chmod("./build.sh", 0777);
    Utils::log("Building {$jobName}", "info");
    passthru("./build.sh", $return);
    if ($return != 0) {
        Utils::log("An error occurred while building the APK.", "error");
        exit(1);
    }

    // Move apk
    chdir(__DIR__);
    exec("mv ./sources/app/build/outputs/apk/release/app-release-unsigned.apk ./{$jobName}.apk");

    // Send apk to server!
    Utils::log("Uploading APK to server", "info");
    $uploadResult = Utils::uploadApk($jobUrl, $appId, "/home/builds/{$jobName}.apk");
    if ($uploadResult != 0 || array_key_exists('error', $uploadResult)) {
        Utils::log("An error occurred while uploading the APK.", "error");
        exit(1);
    }

    if (array_key_exists('success', $uploadResult)) {
        Utils::log("APK successfully uploaded to server.", "success");
    }

    // Clean-up end!
    exec("rm -Rf ./sources ./sources.zip");
    exec("rm -Rf ./*.apk");

    exit(0);
} catch (\Exception $e) {
    Utils::log("Caught global exception {$e->getMessage()}", "error");
    exit(1);
}
