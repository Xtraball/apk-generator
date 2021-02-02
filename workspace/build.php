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
    $buildType = $argv[7];
    $bundleType = ($buildType === 'cdvBuildRelease') ? 'bundleRelease' : 'bundleDebug';
    $keystore = json_decode(base64_decode($argv[8]), true);
    $buildNumber = $argv[9];
    $withAab = $argv[10] === 'aab';

    // Revert to simple java.lock, running on multiple nodes breaks the sequential order!
    $javaLock = "/home/builds/java.lock";

    Utils::logTable([
        'jobUrl' => $jobUrl,
        'jobName' => $jobName,
        'license' => $license,
        'appId' => $appId,
        'appName' => $appName,
        'uuid' => $uuid,
        'buildType' => $buildType,
        'buildNumber' => $buildNumber,
        'withAab' => $withAab,
    ], 'Starting APK/AAB Build');

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

    if (!$checkLicense['isAllowed']) {
        throw new \Exception("You are not allowed to use this service.");
    }

    // We will try to monkey-patch missing domains from krypton!
    $originalJobUrl = $jobUrl;

    $jobUrl = preg_replace('/^((http)(s?)(:\/\/))(https?:\/\/)/', "$2$3$4", $jobUrl);

    if (preg_match('/^https?:\/(\/\-\/)var\//i', $jobUrl) === 1) {
        Utils::log('Skipping source generation.', 'error');
        throw new \Exception('Skipping source generation.');
        /**Utils::log("Bad url \$jobUrl: {$jobUrl}", "error");

        $retry = 0;
        $continue = true;
        $safeStop = 0;
        do {
            $jobUrl = Utils::monkeyPatch($checkLicense, $originalJobUrl, $retry);
            Utils::log("Downloading {$jobUrl}", "info");
            exec("wget --no-check-certificate --quiet $jobUrl -O ./{$uuid}.zip",$o, $return);
            var_dump($return);
            if ($return == 0) {
                $continue = false;
            }
            $retry++;
            sleep(1);
            $safeStop++;
        } while ($continue && ($safeStop < 10) && ($jobUrl !== false));

        if (!is_file("./{$uuid}.zip")) {
            throw new \Exception("An error occurred while downloading the archive {$jobUrl}");
        }*/
    } else {
        // Download archive
        Utils::log("Downloading {$jobUrl}", "info");
        exec("wget -U \"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)\" --no-check-certificate --quiet '$jobUrl' -O ./{$uuid}.zip",$o, $return);
        if ($return != 0) {
            throw new \Exception("An error occurred while downloading the archive {$jobUrl}");
        }
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

    // Gradle hotfix
    Utils::gradleCheck("/home/builds/{$uuid}");

    chmod("./build.sh", 0777);
    Utils::log("Building {$jobName}", "info");
    passthru("./build.sh {$uuid} {$buildNumber} {$buildType} {$bundleType}", $return1);
    // Unlock next job java.lock
    if (is_file($javaLock)) {
        exec("rm -rf {$javaLock}");
    }
    if ($return != 0) {
        throw new \Exception("An error occurred while building the APK.");
    }

    // Move apk
    chdir("/home/builds");
    switch ($buildType) {
        case 'cdvBuildRelease':
        default:
            exec("mv ./{$uuid}/app/build/outputs/apk/release/app-release.apk ./{$jobName}.apk");
            if ($withAab) {
                exec("mv ./{$uuid}/app/build/outputs/bundle/release/app-release.aab ./{$jobName}.aab");
            }
            break;
        case 'cdvBuildDebug':
            exec("mv ./{$uuid}/app/build/outputs/apk/debug/app-debug.apk ./{$jobName}.apk");
            if ($withAab) {
                exec("mv ./{$uuid}/app/build/outputs/bundle/debug/app-debug.aab ./{$jobName}.aab");
            }
            break;
    }

    // Send apk to server!
    Utils::log("Uploading APK & AAB to server", "info");
    Utils::log($jobUrl, "info");
    $uploadResult = Utils::uploadApk($jobUrl, $appId, "/home/builds/{$jobName}.apk", $uploadKeystore, $withAab);
    if (is_array($uploadResult) &&
        array_key_exists('error', $uploadResult)) {

        $message = array_key_exists('message', $uploadResult) ?
            $uploadResult['message'] : '#000-10: Unknown Error.';

        throw new \Exception("An error occurred while uploading the APK, {$message}");
    }

    if (is_array($uploadResult) &&
        array_key_exists('success', $uploadResult)) {

        Utils::log("APK & AAB successfully uploaded to server.", "success");
    } else {
        throw new \Exception("An error occurred while uploading the APK, please check your <b>php.ini</b>" .
            " settings <b>post_max_size=100M</b>, <b>upload_max_filesize=100M</b> and <b>max_input_time=120</b>" .
            " are good values to start with.");
    }

    // Clean-up end!
    exec("rm -rf ./{$uuid} ./{$uuid}.zip");
    exec("rm -rf ./{$jobName}.apk");
    exec("rm -rf ./{$jobName}.aab");

    exit(0);
} catch (\Exception $e) {
    Utils::log("Caught global exception {$e->getMessage()}", "error");
    Utils::updateJobStatus($jobUrl, $appId, 'failed', $e->getMessage());

    // Unlock next job java.lock (just to be sure in case of exception)
    if (is_file($javaLock)) {
        exec("rm -rf {$javaLock}");
    }
    exit(1);
}
