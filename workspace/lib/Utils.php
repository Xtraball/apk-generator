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

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        }

        $result = json_decode($response, true);

        return $result['type'];
    }

    /**
     * @param $jobUrl
     * @param $appId
     * @param $path
     * @return mixed
     * @throws \Exception
     */
    public static function uploadApk($jobUrl, $appId, $path)
    {
        $urlParts = parse_url($jobUrl);
        $url = sprintf("%s://%s/application/backoffice_iosautopublish/uploadapk",
            $urlParts['scheme'],
            $urlParts['host']);

        $post = [
            'appId' => $appId,
            'file[0]' => new \cURLFile($path, 'application/octet-stream', basename($path)),
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

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        }

        $result = json_decode($response, true);

        return $result['type'];
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