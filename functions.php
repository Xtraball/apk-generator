<?php

/**
 * @param array $lines
 * @param null $titleTable
 */
function logTable(array $lines, $titleTable = null)
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
            str_pad($titleTable, $maxTitle + $maxValue + 3, ' ')
        );
    }


    echo str_repeat('-', $lineLength) . "\n";
    foreach ($lines as $title => $value) {
        echo sprintf("| %s | %s |\n",
            str_pad($title, $maxTitle, ' '),
            str_pad($value, $maxValue, ' ')
        );
    }
    echo str_repeat('-', $lineLength) . "\n";
}