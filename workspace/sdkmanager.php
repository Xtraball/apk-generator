<?php

/**
 * @param $command
 * @return mixed
 */
function lexec ($command) {
    exec($command, $result);

    return $result;
}

//$linux = 'https://dl.google.com/android/repository/commandlinetools-linux-8092744_latest.zip';
//$linux = 'https://dl.google.com/android/repository/commandlinetools-linux-10406996_latest.zip';
$linux = 'https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip';
$file = $linux;

// Disabling Android-SDK check on APK Build!
$run = true;
if (is_file(__DIR__ . '/../../../../config.php')) {
    require __DIR__ . '/../../../../config.php';

    if (isset($config) && array_key_exists('disabled', $config)) {
        echo 'Android SDK Updater is disabled in `config.php` !' . PHP_EOL;
        $run = false;
    }
}

if ($run) {
    $toolsPath = dirname(__FILE__);
    chmod($toolsPath, 0777);
    $androidSdkPath = $toolsPath . '/android-sdk';
    if (!@file_exists($androidSdkPath)) {
        mkdir($androidSdkPath, 0777, true);
    }

    // Ensure we have the latest tools!
    if (!is_dir($toolsPath . '/cmdline-tools') ||
        !is_file($toolsPath . '/cmdline-tools/bin/sdkmanager')) {
        lexec("rm -Rf '" . $toolsPath . "/cmdline-tools'");
        lexec("wget '" . $file . "' -O " .
            $toolsPath . "/tools.zip");
        chdir($toolsPath);
        lexec("unzip tools.zip");
    }

    if (!is_dir($androidSdkPath . '/licenses')) {
        lexec("mkdir -p '" . $androidSdkPath . "/licenses'");
    }

    // Manual licenses
    $licenses = [
        "android-googletv-license" => "\n601085b94cd77f0b54ff86406957099ebe79c4d6",
        "android-sdk-license" => "\nd56f5187479451eabf01fb78af6dfcb131a6481e\n24333f8a63b6825ea9c5514f83c2829b004d1fee",
        "android-sdk-preview-license" => "\n84831b9409646a918e30573bab4c9c91346d8abd",
        "google-gdk-license" => "\n33b6a2b64607f11b759f320ef9dff4ae5c47d97a",
        "mips-android-sysimage-license" => "\ne9acab5b5fbb560a72cfaecce8946896ff6aab9d",
    ];
    foreach ($licenses as $filename => $license) {
        $_path = "{$androidSdkPath}/licenses/{$filename}";
        if (!is_file($_path)) {
            file_put_contents($_path, $license);
        }
    }

    file_put_contents($androidSdkPath . "/y.txt",
        implode("\n", array_fill(0, 100, 'y')));
    lexec($toolsPath . '/cmdline-tools/bin/sdkmanager --sdk_root=' . $androidSdkPath . ' ' .
//        '"build-tools;30.0.3" ' .
//        '"build-tools;32.0.0" ' .
        '"build-tools;33.0.2" ' .
        '"build-tools;34.0.0" ' .
        '"platform-tools" ' .
        '"tools" ' .
//        '"platforms;android-29" ' .
//        '"platforms;android-30" ' .
//        '"platforms;android-31" ' .
//        '"platforms;android-32" ' .
        '"platforms;android-33" ' .
        '"platforms;android-34" ' .
        '"extras;android;m2repository" ' .
        '"extras;google;m2repository" ' .
        '"extras;google;google_play_services" ' .
//        '"patcher;v4" ' .
        '< ' . $androidSdkPath . '/y.txt');

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
