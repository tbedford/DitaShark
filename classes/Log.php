<?php 

/*
 * Log class
 * We always write to STDERR so log output can be redirected to a file if required.
 * See "http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/"
 * For details of escape codes / colors etc.
 */

class Log {

    /* ANSI colors */

    const ESC_CODE = "\033["; // escape code to ANSI terminal functions
    const ESC_TERM = "m"; // escape code terminator
    const INFO_COLOR = '0;32'; // green
    const DEBUG_COLOR = '0;36'; // cyan 
    const WARN_COLOR = '0;35'; // purple 
    const FATAL_COLOR = '0;31'; // red
    const COLOR_OFF = "\033[0m";

    // general information about what's going on
    public static function Info ($tag, $msg){

        $msg = "[INFO] "  . '[' . $tag . ']' . ' ' . $msg;
        $msg = self::ESC_CODE . self::INFO_COLOR . self::ESC_TERM . $msg . self::COLOR_OFF . "\n";
        fwrite (STDERR, $msg);
        
    }

    // for debugging purposes only
    public static function Debug ($tag, $msg){

        $msg = "[DEBUG] " . '[' . $tag . ']' . ' ' . $msg;
        $msg = self::ESC_CODE . self::DEBUG_COLOR . self::ESC_TERM . $msg . self::COLOR_OFF . "\n";
        fwrite (STDERR, $msg);

    }

    // warnings to the user
    public static function Warn ($tag, $msg){

        $msg = "[WARN] " . '[' . $tag . ']' . ' ' . $msg;
        $msg = self::ESC_CODE . self::WARN_COLOR . self::ESC_TERM . $msg . self::COLOR_OFF . "\n";
        fwrite (STDERR, $msg);

    }

    // fatal error - cannot continue processing
    public static function Fatal ($tag, $msg){

        $msg = "[FATAL] " . '[' . $tag . ']' . ' ' . $msg;
        $msg = self::ESC_CODE . self::FATAL_COLOR . self::ESC_TERM . $msg . self::COLOR_OFF . "\n";
        fwrite (STDERR, $msg);

    }

}

?>