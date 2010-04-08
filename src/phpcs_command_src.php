#!/usr/bin/php
<?php
/**
 * TextMate PHP_CodeSniffer command.
 */

// IMPORTANT!
// Update the path name to phpcs file here.
// You need local installation of PHP_CodeSniffer.
$phpcsPath = '/usr/local/bin/phpcs';

// Set the following to specify phpcs standard to sniff with
$phpcsStandards = '';

if (file_exists($phpcsPath) === FALSE) {
    echo '<html><head><title>PHP_CodeSniffer Not Found</title></head>';
    echo '<body><h3>We have a problem here!</h3>
    <p>The path to PHP_CodeSniffer executable file is not found on your system.</p>
    <p>Please make sure you set the right path name before using this command.</p>
    <p>If you haven\'t installed PHP_CodeSniffer on your local machine yet, then try to install it first.</p>
    <p>You can find more detailed information about PHP_CodeSniffer package from <a href="http://pear.php.net/package/PHP_CodeSniffer">PEAR website</a>.</p>
    </body></html>';
} else if (is_executable($phpcsPath) === FALSE) {
    echo '<html><head><title>PHP_CodeSniffer Not Found</title></head>';
    echo '<body><h3>We have a problem here!</h3>
    <p>phpcs command line tool is not executable.</p>
    </body></html>';
} else {
    error_reporting((E_ALL | E_STRICT));
    // Get the filename to run the sniffs.
    if (isset($_SERVER['TM_FILEPATH']) === TRUE) {
        $fileName = $_SERVER['TM_FILEPATH'];
    } else {
        $fileName = dirname(__FILE__).'/test.php';
    }

    $result = array();
    $result['errors']   = array();
    $result['warnings'] = array();

    // Run PHP_CodeSniffer with csv mode on the file.
    $output  = array();
    $options = array('--report=csv');
    $options[] = ($phpcsStandards) ? '--standard='.$phpcsStandards : '';
    $command = $phpcsPath.' '.implode(' ', $options).' '.$fileName;
    exec($command, $output);

    // Collect the key indexes we are looking for.
    $fields      = array(
                    'File',
                    'Line',
                    'Column',
                    'Severity',
                    'Message',
                   );
    $keys        = array();
    $headLineArr = explode(',', $output[0]);
    foreach ($fields as $field) {
        $idx = array_search($field, $headLineArr);
        if ($idx !== FALSE) {
            $keys[$field] = $idx;
        }
    }

    // Loop through output array populating $result array.
    $outputSize  = count($output);
    $numOfFields = count($fields);
    for ($i = 1; $i < $outputSize; $i++) {
        $lineArr = explode(',', $output[$i]);

        // Comma separator might cause a problem here.
        // Do some manual parsing.
        if (count($lineArr) !== $numOfFields) {
            $tmp      = array();
            $newToken = '';
            $concat   = FALSE;

            foreach ($lineArr as $token) {
                if ($concat === TRUE) {
                    if (substr($token, -1) === '"') {
                        $concat    = FALSE;
                        $newToken .= $token;
                        $tmp[]     = $newToken;
                        $newToken  = '';
                    } else {
                        $newToken .= $token.' ';
                    }
                } else {
                    if ($token[0] === '"') {
                        if (substr($token, -1) === '"') {
                            $tmp[] = $token;
                        } else {
                            $newToken .= $token.' ';
                            $concat = TRUE;
                        }
                    } else {
                        $tmp[] = $token;
                    }
                }
            }

            $lineArr = $tmp;
        }//end if

        if ($lineArr[$keys['Severity']] === 'error') {
            $resultKey = 'errors';
        } else if ($lineArr[$keys['Severity']] === 'warning') {
            $resultKey = 'warnings';
        }

        $msg = str_replace('\"', '"', substr($lineArr[$keys['Message']], 1, -1));

        $result[$resultKey][] = array(
                                 'line'   => $lineArr[$keys['Line']],
                                 'msg'    => $msg,
                                 'column' => $lineArr[$keys['Column']],
                                );
    }//end for

    $result['errors'] = array_reverse($result['errors']);
    $result['warnings'] = array_reverse($result['warnings']);
    $errorNum = count($result['errors']);
    $warnNum  = count($result['warnings']);
    ?>

    <html>
    <head>
    <title>PHP_CodeSniffer, <?php echo $fileName; ?></title>
    <style type="text/css">
    body {
        background-color: #ffffff;
        color: red;
        font-family: Arial,Helvetica,sans-serif;
        font-size: 12px;
    }
    a, a:visited {
        text-decoration: none;
    }
    a:hover {
        color: black;
    }

    .error {
      cursor: pointer;
        border: 2px solid red;
        background-color: #fff2f2;
        width: 95%;
        color: red;
        margin-bottom: 7px;
        padding: 3px;
    }

    .error.over {
        background-color: #C64949;
        color: #ffffff;
    }

    .warning {
        border: 2px solid #9C8023;
        background-color: #feffc2;
        color: black;
        width:95%;
        margin-bottom: 7px;
        padding: 3px;
    }

    .warning.over {
      background-color: #9C8023;
      color: #ffffff;
    }

    .type {
        font-weight: bold;
    }
    .error-msg {
        margin-top: 2px;
    }
    .summary {
        width: 80%;
        margin-bottom: 3px;
        padding-bottom: 3px;
        color: black;
    }
    .footer {
        border-top: 1px solid black;
        width: 95%;
        margin-top: 3px;
        padding-top: 3px;
        color: black;
    }
    </style>
    <script type="text/javascript">
    var errors   = <?php echo count($result['errors']); ?>;
    var warnings = <?php echo count($result['warnings']); ?>;
    function init() {
      var types = [
        {
          className: 'error',
          classPrefix: 'e',
          count: errors
        },
        {
          className: 'warning',
          classPrefix: 'w',
          count: warnings
        }
      ];

      var typesLen = types.length;

      for (var i = 0; i < typesLen; i++) {
        for (var j = 1; j <= types[i].count; j++) {
          (function(idx, classN, classP) {
            var id           = classP + idx;
            var eElem        = document.getElementById(id);
            var textMateLink = eElem.getAttribute('txmt');
            var locked       = false;
            eElem.onmouseover = function() {
              if (locked === false) {
                eElem.className = classN + ' over';
              }
            };

            eElem.onmouseout = function() {
              if (locked === false) {
                eElem.className = classN;
              }
            };

            eElem.onclick = function() {
              if (locked === true) {
                eElem.className = classN;
              }

              locked = !locked;

              window.location = textMateLink;
            };
          }) (j, types[i].className, types[i].classPrefix);
        }//end for types[i].count
      }//end for typesLen
    }

    </script>
    </head>
    <body onload="init();">

    <div class="summary"><?php
    $summary  = 'Found <strong>'.$errorNum. '</strong> error';
    $summary .= ($errorNum > 1 ? 's': '');
    $summary .= ' <strong>'.$warnNum. '</strong> warning';
    $summary .= ($warnNum > 1 ? 's': '');
    echo $summary;
    ?></div>

    <?php
    if (empty($result) === TRUE) {
        // Print something.
    } else {
        $fileUrlPrefix = 'txmt://open?url=file://';
        $fileName      = $fileUrlPrefix.$fileName;

        // Errors.
        $count = 1;
        foreach ($result['errors'] as $error) {
            echo '
                <div id="e'.$count.'" class="error" txmt="'.$fileName.'&line='.$error['line'].'&column='.$error['column'].'">
                    <span class="type">Error</span>
                    <span class="line">(line '.$error['line'].')</span>
                    <div class="error-msg">'.$error['msg'].'</div>
                </div>
            ';
            $count++;
        }//end foreach

        // Warnings.
        $count = 1;
        foreach ($result['warnings'] as $warning) {
            echo '
                <div id="w'.$count.'" class="warning" txmt="'.$fileName.'&line='.$warning['line'].'&column='.$warning['column'].'">
                    <span class="type">Warning</span>
                    <span class="line">(line '.$warning['line'].')</span>
                    <div class="error-msg">'.$warning['msg'].'</div>
                </div>
            ';
            $count++;
        }//end foreach
    }//end if
    ?>

    <div class="footer">
    <?php
    $versionCommand = $phpcsPath.' --version';
    $output = array();
    exec($versionCommand, $output);
    echo htmlentities(trim($output[0]));
    ?>
    </div>

    </body>
    </html>
    <?php
}
?>