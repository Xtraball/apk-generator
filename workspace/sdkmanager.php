<?php

/**
 * @param $command
 * @return mixed
 */
function lexec ($command) {
    exec($command, $result);

    return $result;
}

$tools = 'https://dl.google.com/android/repository/sdk-tools-linux-3859397.zip';

$run = true;
if ($run) {
    $toolsPath = dirname(__FILE__);
    chmod($toolsPath, 0777);
    $androidSdkPath = $toolsPath . '/android-sdk';
    if (!@file_exists($androidSdkPath)) {
        mkdir($androidSdkPath, 0777, true);
    }

    // Ensure we have the latest tools!
    if (!is_dir($androidSdkPath . '/tools') ||
        !is_file($androidSdkPath . '/tools/bin/sdkmanager')) {
        lexec("rm -Rf '" . $androidSdkPath . "'");
        if (!@file_exists($androidSdkPath)) {
            mkdir($androidSdkPath, 0777, true);
        }
        lexec("wget '" . $tools . "' -O " .
            $androidSdkPath . "/tools.zip");
        chdir($androidSdkPath);
        lexec("unzip tools.zip");
    }

    if (!is_dir($androidSdkPath . '/licenses')) {
        lexec("mkdir -p '" . $androidSdkPath . "/licenses'");
    }

    file_put_contents($androidSdkPath . "/licenses/android-sdk-license",
        "\nd56f5187479451eabf01fb78af6dfcb131a6481e");
    file_put_contents($androidSdkPath . "/y.txt",
        implode("\n", array_fill(0, 100, 'y')));
    lexec($androidSdkPath . '/tools/bin/sdkmanager ' .
        '"build-tools;27.0.3" ' .
        '"build-tools;28.0.3" ' .
        '"platform-tools" ' .
        '"tools" ' .
        '"platforms;android-27" ' .
        '"platforms;android-28" ' .
        '"extras;android;m2repository" ' .
        '"extras;google;m2repository" ' .
        '"extras;google;google_play_services" ' .
        '"patcher;v4" < ' . $androidSdkPath . '/y.txt');

    // Clean-up!
    if (is_file($androidSdkPath . "/y.txt")) {
        unlink($androidSdkPath . "/y.txt");
    }
    if (is_file($androidSdkPath . "/tools.zip")) {
        unlink($androidSdkPath . "/tools.zip");
    }

    lexec("chmod -R 777 '" . $androidSdkPath . "'");
    echo 'android-sdk is up to date.' . PHP_EOL;
}