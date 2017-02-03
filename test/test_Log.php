#!/usr/bin/env php

<?php

require_once '../classes/Log.php';

echo "\033[1;31mTest String\033[0m\n"; // sample escape code sequence

Log::Info('Info message.');
Log::Debug('Debug message.');
Log::Warn('Warn message.');
Log::Fatal('Fatal message.');

?>
