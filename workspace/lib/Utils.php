<?php

namespace Cli;

/**
 * Class Utils
 */
class Utils
{
    /**
     * @param array $lines
     * @param null $titleTable
     */
    public static function logTable(array $lines, $titleTable = null)
    {
        $maxTitle = 0;
        $maxValue = 0;
        foreach ($lines as $title => $value) {
            if (strlen($title) > $maxTitle) {
                $maxTitle = strlen($title);
            }
            if (strlen($value) > $maxValue) {
                $maxValue = strlen($value);
            }
        }

        $lineLength = $maxTitle + $maxValue + 7;

        if ($titleTable !== null) {
            echo str_repeat('-', $lineLength) . "\n";
            echo sprintf("| %s |\n",
                color(str_pad($titleTable, $maxTitle + $maxValue + 3, ' '), 'green')
            );
        }


        echo str_repeat('-', $lineLength) . "\n";
        foreach ($lines as $title => $value) {
            echo sprintf("| %s | %s |\n",
                color(str_pad($title, $maxTitle, ' '), 'blue'),
                str_pad($value, $maxValue, ' ')
            );
        }
        echo str_repeat('-', $lineLength) . "\n";
    }

    /**
     * @param $message
     * @param string $level
     */
    public static function log($message, $level = 'info')
    {
        switch ($level) {
            case 'success':
                echo sprintf("%s %s", color('[SUCCESS]', 'green'),
                        $message) . "\n";
                break;
            case 'debug':
                echo sprintf("%s %s", color('[DEBUG]', 'purple'),
                        $message) . "\n";
                break;
            case 'info':
            default:
                echo sprintf("%s %s", color('[INFO]', 'blue'),
                        $message) . "\n";
                break;
            case 'error':
                echo sprintf("%s %s", color('[ERROR]', 'red'),
                        color($message, 'red')) . "\n";
                break;
            case 'warning':
                echo sprintf("%s %s", color('[WARNING]', 'yellow'),
                        color($message, 'yellow')) . "\n";
                break;
        }
    }

    /**
     * @param $license
     * @return mixed
     * @throws \Exception
     */
    public static function checkLicense($license)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://services.siberiancms.com/api/external/license/check",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"licenseKey\": \"{$license}\"}",
            CURLOPT_HTTPHEADER => [
                "content-type: application/json"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        Utils::log('== RAW Response ==', 'debug');
        Utils::log($response, 'debug');

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @param $jobUrl
     * @param $appId
     * @param $path
     * @param $keystore
     * @param $withAab
     * @return mixed
     * @throws \Exception
     */
    public static function uploadApk($jobUrl, $appId, $path, $keystore = false, $withAab = false)
    {
        $urlParts = parse_url($jobUrl);
        $url = sprintf("%s://%s/application/backoffice_iosautopublish/uploadapk",
            $urlParts['scheme'],
            $urlParts['host']);

        $index = 0;
        $post = [
            'appId' => $appId,
            "file[$index]" => new \cURLFile($path, 'application/octet-stream', basename($path)),
        ];
        $index++;

        if ($keystore !== false) {
            $post["file[$index]"] = new \cURLFile($keystore, 'application/octet-stream', basename($keystore));
            $index++;
        }

        if ($withAab) {
            $aabFile = str_replace('.apk', '.aab', $path);
            $post["file[$index]"] = new \cURLFile($aabFile, 'application/octet-stream', basename($aabFile));
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        Utils::log('== RAW Response ==', 'debug');
        Utils::log($response, 'debug');

        if ($err) {
            throw new \Exception($err);
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @param $jobUrl
     * @param $appId
     * @param $status
     * @return mixed
     */
    public static function updateJobStatus($jobUrl, $appId, $status, $message)
    {
        $urlParts = parse_url($jobUrl);
        $url = sprintf("%s://%s/application/backoffice_iosautopublish/apkservicestatus",
            $urlParts['scheme'],
            $urlParts['host']);

        $post = [
            'appId' => $appId,
            'status' => $status,
            'message' => $message,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        Utils::log('== RAW Response ==', 'debug');
        Utils::log($response, 'debug');

        Utils::log("Updating job status to {$status}, {$message}", 'info');

        curl_close($curl);

        if ($err) {
            Utils::log($err, 'error');
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @param $keystore
     * @param string $keystorePath
     * @throws \Exception
     */
    public static function generateKeystore($keystore, $uuid, $keystorePath = './keystore.pks')
    {
        $command = sprintf("keytool -genkeypair -keyalg RSA -noprompt -alias \"{$keystore['alias']}\" \
                                      -dname 'CN={$keystore['organization']}, O={$keystore['organization']}' \
                                      -keystore {$keystorePath} \
                                      -storepass {$keystore['storepass']} \
                                      -keypass {$keystore['storepass']} \
                                      -validity 36135");

        Utils::log("Generating keystore!", 'info');

        exec($command, $o, $return);

        if ($return !== 0) {
            throw new \Exception('Unable to generate the keystore, ' . print_r($keystore, true));
        }
    }

    /**
     * @param $keystore
     * @param string $keystorePath
     * @throws \Exception
     */
    public static function convertKeystore($keystore, $uuid, $keystorePath = './keystore.pks')
    {
        $oldKeystorePath = str_replace('.pks', '.old.pks', $keystorePath);
        copy($keystorePath, $oldKeystorePath);

        $command = sprintf("keytool -importkeystore \
                                    -noprompt \
                                    -alias \"{$keystore['alias']}\" \
                                    -srcalias \"{$keystore['alias']}\" \
                                    -srckeystore {$oldKeystorePath} \
                                    -srcstorepass {$keystore['storepass']} \
                                    -srcstoretype JKS \
                                    -srckeypass {$keystore['keypass']} \
                                    -destalias \"{$keystore['alias']}\" \
                                    -destkeystore {$keystorePath} \
                                    -deststoretype PKCS12 \
                                    -deststorepass {$keystore['storepass']}");

        Utils::log("converting keystore!", 'info');

        exec($command, $o, $return);

        if ($return !== 0) {
            Utils::log('Unable to convert the keystore (it is probably ok), ' . print_r($keystore, true) . print_r($o, true), 'info');
        }
    }

    /**
     * @param $keystore
     * @param string $keystorePath
     * @return bool
     */
    public static function isPks($keystore, $keystorePath = './keystore.pks')
    {
        exec("keytool -list -keystore {$keystorePath} -storepass {$keystore['storepass']}", $o);
        $output = implode(' ', $o);

        return (stripos($output, "Keystore type: JKS") !== false);
    }

    /**
     * @param $keystore
     * @param $uuid
     */
    public static function patchProperties ($keystore, $uuid)
    {
        // Monkey patching properties
        exec("sed -i s/{$keystore['keypass']}/{$keystore['storepass']}/g /home/builds/{$uuid}/release-signing.properties");
    }

    /**
     * @param $keystore
     * @param $uuid
     */
    public static function patchSdkApiLevel($uuid)
    {
        // Monkey patch Google API Sdk version
        $configXmlPath = "/home/builds/{$uuid}/app/src/main/res/xml/config.xml";
        $configXml = file_get_contents($configXmlPath);
        $configXml = preg_replace('/<preference name="android-targetSdkVersion" value="([0-9]+)" \/>/i', '<preference name="android-targetSdkVersion" value="33" />', $configXml);
        file_put_contents($configXmlPath, $configXml);

        // Open file siberian/var/apps/ionic/android/project.properties and replace target=android-XX with target=android-33
        $projectPropertiesPath = "/home/builds/{$uuid}/project.properties";
        $projectProperties = file_get_contents($projectPropertiesPath);
        $projectProperties = preg_replace('/target=android-([0-9]+)/i', 'target=android-33', $projectProperties);
        file_put_contents($projectPropertiesPath, $projectProperties);

        // Same with file siberian/var/apps/ionic/android/CordovaLib/project.properties
        $cordovaLibProjectPropertiesPath = "/home/builds/{$uuid}/CordovaLib/project.properties";
        $cordovaLibProjectProperties = file_get_contents($cordovaLibProjectPropertiesPath);
        $cordovaLibProjectProperties = preg_replace('/target=android-([0-9]+)/i', 'target=android-33', $cordovaLibProjectProperties);
        file_put_contents($cordovaLibProjectPropertiesPath, $cordovaLibProjectProperties);

        // Same with file siberian/var/apps/ionic/android/cdv-gradle-config.json   "SDK_VERSION": XX, with   "SDK_VERSION": 33,
        $cdvGradleConfigPath = "/home/builds/{$uuid}/cdv-gradle-config.json";
        $cdvGradleConfig = file_get_contents($cdvGradleConfigPath);
        $cdvGradleConfig = preg_replace('/"SDK_VERSION": ([0-9]+)/i', '"SDK_VERSION": 33', $cdvGradleConfig);
        file_put_contents($cdvGradleConfigPath, $cdvGradleConfig);

        // Read all three files and log them for debug purposes, forcing file_get_contents
        // to read the file from disk and not from cache
        Utils::log("config.xml", "debug");
        Utils::log(file_get_contents($configXmlPath), "debug");

        Utils::log("project.properties", "debug");
        Utils::log(file_get_contents($projectPropertiesPath), "debug");

        Utils::log("CordovaLib/project.properties", "debug");
        Utils::log(file_get_contents($cordovaLibProjectPropertiesPath), "debug");

        Utils::log("cdv-gradle-config.json", "debug");
        Utils::log(file_get_contents($cdvGradleConfigPath), "debug");

    }

    /**
     * @param $jobUrl
     * @param $appId
     * @param $keystore
     * @param string $keystorePath
     */
    public static function backupKeystore($jobUrl, $appId, $keystore, $keystorePath = './keystore.pks')
    {
        $urlParts = parse_url($jobUrl);

        if (!is_file($keystorePath)) {
            Utils::log("Unable to find keystore file at {$keystorePath}, trying to generate it!", "debug");
            // Generating it
            Utils::generateKeystore($keystore, null, $keystorePath);

            if (!is_file($keystorePath)) {
                Utils::log("Unable to find keystore file at {$keystorePath}.", "error");
                return;
            }
        }

        $data = [
            'host' => $urlParts['host'],
            'appId' => $appId,
            'keystore' => $keystore,
            'keystoreRaw' => bin2hex(file_get_contents($keystorePath))
        ];

        // Create triple level of folders Year/Month/Day to be sure we won't reach the file limits!
        $path = sprintf("/home/builds/keystore/%s/%s/%s/appId-%s_hostname-%s_%s.json",
            date('Y'),
            date('m'),
            date('d'),
            $appId,
            slugify($urlParts['host']),
            time());

        $folder = dirname($path);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        file_put_contents($path, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        Utils::log("Keystore backed-up to {$path}.", "success");
    }

    /**
     * @param $checkLicense
     * @param $jobUrl
     * @param int $retry
     * @return mixed
     * @throws \Exception
     */
    public static function monkeyPatch($checkLicense, $jobUrl, $retry = 0)
    {
        try {
            // We will try to get domain from Krypton
            if (array_key_exists('hosts', $checkLicense)) {
                $hosts = json_decode($checkLicense['hosts'], true);
                if (isset($hosts[$retry])) {
                    $jobUrl = str_replace('://-/var', '://' . $hosts[$retry] . '/var', $jobUrl);

                    Utils::log("Monkey-Patching \$jobUrl: {$jobUrl}", "info");
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return $jobUrl;
    }

    /**
     * @param $path
     */
    public static function gradleCheck($path)
    {
        try {
            $baseBuildGradlePath = "{$path}/Push/base-build.gradle";
            $baseBuildGradle = file_get_contents($baseBuildGradlePath);
            $baseBuildGradle = str_replace("com.android.tools.build:gradle:+", "com.android.tools.build:gradle:3.3.0", $baseBuildGradle);
            file_put_contents($baseBuildGradlePath, $baseBuildGradle);

            $pluginBuildGradlePath = "{$path}/cordova/lib/plugin-build.gradle";
            $pluginBuildGradle = file_get_contents($pluginBuildGradlePath);
            $pluginBuildGradle = str_replace("com.android.tools.build:gradle:1.0.0+", "com.android.tools.build:gradle:3.3.0", $pluginBuildGradle);
            file_put_contents($pluginBuildGradlePath, $pluginBuildGradle);

            Utils::log("Gradle-Check \$path: {$path} SUCCESS", "success");

        } catch (\Exception $e) {
            Utils::log("Gradle-Check \$path: {$path} ERROR", "error");
        }
    }
}

/**
 * Global helper
 *
 * @param $string
 * @param null $fg
 * @param null $bg
 * @return string
 */
function color($string, $fg = null, $bg = null)
{
    return \Cli\Colors::initColoredString($string, $fg, $bg);
}

/**
 * @param $text
 * @return null|string|string[]
 */
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
        return uniqid();
    }

    return $text;
}