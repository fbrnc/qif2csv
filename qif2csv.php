<?php


$csv = qif('export.QIF');

$fp = fopen('file.csv', 'w');
foreach ($csv as $fields) {
    fputcsv($fp, $fields);
}
fclose($fp);


/*
$months = array(array('month','max','min'));

foreach ($csv as $row) {
    $a = floatval($row['sum']);
    $month = date('Y_m', strtotime($row["date"]));
    $months[$month]['month'] = $month;
    if (isset($months[$month]['max'])) {
        $months[$month]['max'] = max($months[$month]['max'], $a);
    } else {
        $months[$month]['max'] = $a;
    }
    if (isset($months[$month]['min'])) {
        $months[$month]['min'] = min($months[$month]['min'], $a);
    } else {
        $months[$month]['min'] = $a;
    }
}

// var_dump($months);

$fp = fopen('months.csv', 'w');
foreach ($months as $fields) {
    fputcsv($fp, $fields);
}
fclose($fp);
*/



/**
 * Will process a given QIF file. Will loop through the file and will send all transactions to the transactions API.
 *
 * @see http://stackoverflow.com/questions/7996051/parse-qif-file-using-php
 * @param string $file
 * @return array
 */
function qif($file, $offset=0)
{

    $lines = file($file);

    $records = array();
    $record = array();
    $end = 0;

    foreach ($lines as $line) {
        /*
        For each line in the QIF file we will loop through it
        */

        $line = trim($line);

        if ($line === "^") {
            /*
            We have found a ^ which indicates the end of a transaction
            we now set $end as true to add this record array to the master records array
            */

            $end = 1;

        } elseif (preg_match("#^!Type:(.*)$#", $line, $match)) {
            /*
            This will be matched for the first line.
            You can get the type by using - $type = trim($match[1]);
            We dont want to do anything with the first line so we will just ignore it
            */
        } else {
            switch (substr($line, 0, 1)) {
                case 'D':
                    /*
                    Date. Leading zeroes on month and day can be skipped. Year can be either 4 digits or 2 digits or '6 (=2006).
                    */
                    $record['date'] = trim(substr($line, 1));
                    break;
                case 'T':
                    /*
                    Amount of the item. For payments, a leading minus sign is required.
                    */
                    $record['amount'] = trim(substr($line, 1));
                    $record['amount'] = str_replace(',', '', $record['amount']);
                    $record['sum'] = $offset + floatval($record['amount']);
                    $offset = $record['sum'];
                    break;
                case 'P':
                    /*
                    Payee. Or a description for deposits, transfers, etc.

                    Note: Yorkshite Bank has some funny space in between words in the description
                    so we will get rid of these
                    */
                    $line = htmlentities($line);
                    $line = str_replace("  ", "", $line);
                    //$line = str_replace(array("&pound;","£"), 'GBP', $line);

                    $record['payee'] = trim(substr($line, 1));
                    break;
                case 'N':
                    /*
                    Investment Action (Buy, Sell, etc.).
                    */
                    $record['investment'] = trim(substr($line, 1));
                    break;
            }

        }


        if ($end == 1) {
            // We have reached the end of a transaction so add the record to the master records array
            $records[] = $record;

            // Rest the $record array
            $record = array();

            // Set $end to false so that we can now build the new record for the next transaction
            $end = 0;
        }

    }

    return $records;
}

