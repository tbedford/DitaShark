<?php

/**
 * MapReader class
 * Reads the root ditamap file.
 *
 * Reads ditamaps recursively building a list of DITA files.
 * The list of files generated uses absolute file names as returned by 
 * the PHP function realpath()
 *
 */

require_once 'Log.php';

class MapReader {

    const TAG = 'MapReader';

    private $xml_parser = null;
    private $ditamap_stack = array(); // push ditamaps to process here, pop them when completed current map
    private $ditafile_stack = array(); // push dita files to process here, pass back to caller
    private $cwd_stack = array();

    function __construct ()
    {
        Log::Debug(self::TAG, "__construct");
    }

    function __destruct () 
    {
        Log::Debug(self::TAG, "__destruct");
    }


    private function get_base ($filename)
    {
        $parts = pathinfo($filename);
        return $parts['dirname'];
    }

    
    private function get_filename ($filename)
    {
        $parts = pathinfo($filename);
        return $parts['filename'].'.'.$parts['extension'];
    }

    function startElementHandler($parser, $element, $attributes)
    {

        switch ($element)
        {

        case 'TOPICREF':
            
            if ($attributes['FORMAT'] == "ditamap"){ // ditamap
                Log::Debug (self::TAG, "DITA MAP:" . $attributes['HREF']." pushed to stack.");
                $realname = realpath($attributes['HREF']);
                if (!$realname){
                    Log::Fatal(self::TAG, "file " . $attributes['HREF'] . " not found.");
                    exit (-1);
                }
                array_push($this->ditamap_stack, $realname);
            }
            else { // dita file
                $realname = realpath($attributes['HREF']);
                if (!$realname){
                    Log::Fatal(self::TAG, "file " . $attributes['HREF'] . " not found.");
                    exit (-1);
                }
                array_push($this->ditafile_stack, $realname);
            }
            break;

        } // end switch
    }

    function endElementHandler($parser, $element){

    }

    function characterDataHandler($parser, $cdata){

    }

    function defaultHandler($parser, $text){
        //FIXME: print ("defaultHandler: $text\n");
    }

    public function parse ($filename)
    {

        Log::Debug (self::TAG, "parse: open $filename");
        $fh = fopen($filename, 'r');
        
        if (!$fh){
            Log::Fatal(self::TAG, "Failed to open file: $filename");
            exit(-1);
        }

        $this->xml_parser = xml_parser_create("UTF-8");

        xml_set_object($this->xml_parser, $this);

        xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($this->xml_parser, XML_OPTION_SKIP_WHITE, true);

        xml_set_element_handler($this->xml_parser, 'startElementHandler', 'endElementHandler');
        xml_set_character_data_handler($this->xml_parser, 'characterDataHandler');
        xml_set_default_handler($this->xml_parser, 'defaultHandler');


        $blockSize = 4 * 1024;
        while ($data = fread($fh, $blockSize)){

            if (!xml_parse($this->xml_parser, $data, feof($fh))){
                Log::Fatal(self::TAG, 'Parse error: ' .
                    xml_error_string(xml_get_error_code($this->xml_parser)) .
                    " at line " .
                    xml_get_current_line_number($this->xml_parser));
                exit(-1);
            }
        }
        Log::Debug (self::TAG, "Closing $filename");
        fclose($fh);

        xml_parser_free($this->xml_parser);

    }

    public function start ($filename)
    {        
        $realname = realpath($filename);
        if (!$realname){
            Log::Fatal(self::TAG, "file $realname not found.");
            exit (-1);
        }
        array_push ($this->ditamap_stack, $realname); // FIXME: $this->ditamap_stack[] = $filename is more efficient
        do {
            Log::Debug(self::TAG, "popping...");
            $filename = array_pop($this->ditamap_stack);
            $path = $this->get_base($filename);
            $filename = $this->get_filename($filename);
            chdir($path);
            Log::Debug(self::TAG, "CWD: " . getcwd());
            $this->parse($filename);
        }
        while (!empty($this->ditamap_stack));
        
        return $this->ditafile_stack; 
    }

} // end of class
?>