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

    // Revert to simple java.lock, running on multiple nodes breaks the sequential order!
    $nextJavaLock = "/home/builds/java.lock";
    touch($nextJavaLock);

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
            throw new \Exception("You are not allowed to use this service.");
        }
    }

    $checkLicense = Utils::checkLicense($license);
    if (array_key_exists('error', $checkLicense)) {
        throw new \Exception("An error occurred while checking the license.");
    }

    Utils::log("Type is {$checkLicense['type']}", "info");

    switch (strtolower($checkLicense['type']))
    {
        case 'mae hosted':
        case 'pe hosted':
            // Ok!
            break;
        default:
            throw new \Exception("You are not allowed to use this service.");
    }

    // Download archive
    Utils::log("Downloading {$jobUrl}", "info");
    exec("wget --no-check-certificate --quiet $jobUrl -O ./{$uuid}.zip",$o, $return);
    if ($return != 0) {
        throw new \Exception("An error occurred while downloading the archive {$jobUrl}");
    }
    exec("unzip ./{$uuid}.zip -d ./{$uuid}");

    // Should we generate keystore!
    $uploadKeystore = false;
    if ($keystore['generate'] === true) {
        Utils::generateKeystore($keystore, "/home/builds/{$uuid}/keystore.pks");
        $uploadKeystore = "/home/builds/{$uuid}/keystore.pks";
    }

    // Backup keystore!
    try {
        Utils::backupKeystore($jobUrl, $appId, $keystore, "/home/builds/{$uuid}/keystore.pks");
    } catch (\Exception $e) {
        // We don't fail on backup issue, but we log it!
        Utils::log("Unable to backup keystore data.", "error");
    }

    chmod("./build.sh", 0777);
    Utils::log("Building {$jobName}", "info");
    passthru("./build.sh {$uuid} {$buildNumber}", $return);
    // Unlock next job java.lock
    if (is_file($nextJavaLock)) {
        unlink($nextJavaLock);
    }
    if ($return != 0) {
        throw new \Exception("An error occurred while building the APK.");
    }

    // Move apk
    chdir("/home/builds");
    exec("mv ./{$uuid}/app/build/outputs/apk/release/app-release.apk ./{$jobName}.apk");

    // Send apk to server!
    Utils::log("Uploading APK to server", "info");
    $uploadResult = Utils::uploadApk($jobUrl, $appId, "/home/builds/{$jobName}.apk", $uploadKeystore);
    if (array_key_exists('error', $uploadResult)) {
        throw new \Exception("An error occurred while uploading the APK, {$uploadResult['message']}");
    }

    if (array_key_exists('success', $uploadResult)) {
        Utils::log("APK successfully uploaded to server.", "success");
    }

    // Clean-up end!
    exec("rm -Rf ./{$uuid} ./{$uuid}.zip");
    exec("rm -Rf ./{$jobName}.apk");

    exit(0);
} catch (\Exception $e) {
    Utils::log("Caught global exception {$e->getMessage()}", "error");
    Utils::updateJobStatus($jobUrl, $appId, 'failed', $e->getMessage());

    // Unlock next job java.lock (just to be sure in case of exception)
    if (is_file($nextJavaLock)) {
        unlink($nextJavaLock);
    }
    exit(1);
}
