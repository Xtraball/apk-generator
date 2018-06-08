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
            CURLOPT_URL => "http://krypton.siberiancms.com/siberian-licenses/check",
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
     * @return mixed
     * @throws \Exception
     */
    public static function uploadApk($jobUrl, $appId, $path, $keystore = false)
    {
        $urlParts = parse_url($jobUrl);
        $url = sprintf("%s://%s/application/backoffice_iosautopublish/uploadapk",
            $urlParts['scheme'],
            $urlParts['host']);

        $post = [
            'appId' => $appId,
            'file[0]' => new \cURLFile($path, 'application/octet-stream', basename($path)),
        ];

        if ($keystore !== false) {
            $post['file[1]'] = new \cURLFile($keystore, 'application/octet-stream', basename($keystore));
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
    public static function generateKeystore($keystore, $keystorePath = './keystore.pks')
    {
        $command = sprintf("keytool -genkeypair -keyalg RSA -noprompt -alias {$keystore['alias']} \
                                      -dname 'CN={$keystore['organization']}, O={$keystore['organization']}' \
                                      -keystore {$keystorePath} \
                                      -storepass {$keystore['storepass']} \
                                      -keypass {$keystore['keypass']} \
                                      -validity 36135");

        Utils::log("Generating keystore!", 'info');

        exec($command, $o, $return);

        if ($return !== 0) {
            throw new \Exception('Unable to generate the keystore, '. print_r($keystore, true));
        }
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

        file_put_contents($path, json_encode($data));

        Utils::log("Keystore backed-up to {$path}.", "success");
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
function color ($string, $fg = null, $bg = null)
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