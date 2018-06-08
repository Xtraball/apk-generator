<?php

namespace Cli;

require_once './lib/Colors.php';
require_once './lib/Utils.php';

try {
    Utils::logTable($argv, 'RAW Arguments');

    $jobUrl = base64_decode($argv[1]);
    $jobName = base64_decode($argv[2]);
    $license = base64_decode($argv[3]);
    $appId = base64_decode($argv[4]);
    $appName = $argv[5];
    $uuid = $argv[6];
    $keystore = json_decode(base64_decode($argv[7]), true);
    $buildNumber = $argv[8];

    Utils::logTable([
        'jobUrl' => $jobUrl,
        'jobName' => $jobName,
        'license' => $license,
        'appId' => $appId,
        'appName' => $appName,
        'uuid' => $uuid,
        'buildNumber' => $buildNumber,
    ], 'Starting APK Build');

    Utils::logTable($keystore, 'Keystore informations');

    // Test licence ("MAE Hosted", "PE Hosted") are only allowed!
    if (is_file('./exclude.json')) {
        $exceptions = json_decode(file_get_contents('./exclude.json'), true);

        Utils::logTable($exceptions, "Excluded licenses");

        if (in_array($license, $exceptions)) {
            Utils::log("You are not allowed to use this service.", "error");
            exit(1);
        }
    }

    $checkLicense = Utils::checkLicense($license);
    if (array_key_exists('error', $checkLicense)) {
        Utils::log("An error occurred while checking the license.", "error");
        exit(1);
    }

    Utils::log("Type is {$checkLicense['type']}", "info");

    switch (strtolower($checkLicense['type']))
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

    // Should we generate keystore!
    $uploadKeystore = false;
    if ($keystore['generate'] === true) {
        Utils::generateKeystore($keystore, './sources/keystore.pks');
        $uploadKeystore = '/home/builds/sources/keystore.pks';
    }

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
    $uploadResult = Utils::uploadApk($jobUrl, $appId, "/home/builds/{$jobName}.apk", $uploadKeystore);
    if (array_key_exists('error', $uploadResult)) {
        Utils::log("An error occurred while uploading the APK, {$uploadResult['message']}", "error");
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
