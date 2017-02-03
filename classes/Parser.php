<?php

require_once 'HTMLRenderer.php';
require_once 'Log.php';

/**
 * Class Parser
 * This class processes the DITA XML and records state - for example "inside a P tag, inside a SECTION".
 * When the Parser is created it is linked to a renderer object - the renderer's methods are called by the parser's
 * event handlers. The renderer can call back to determine state through the public method is_inside_tag() method.
 * The 'main' method is parse(), which external programs use to kick off the main rendering process.
 *
 * The main reason for the separation of concerns here is to allow for other renderers to be more easily plugged in
 * in the future, such as PDF and database.
 */

class Parser
{
    const TAG = 'Parser';
    
    // renderers must implement each of these methods for ALL supported tags
    const TAG_OPEN_METHOD_NAME = 'handle_open_';
    const TAG_CLOSE_METHOD_NAME = 'handle_close_';


    // ALL supported DITA tags MUST be listed in here!
    private $tags = array
        ('P', 'CONCEPT', 'TASK', 'REFERENCE', 'CONBODY', 'STHEAD', 'SECTION', 'CODEBLOCK', 'B', 'I',
         'SIMPLETABLE', 'STROW', 'STENTRY', 'XREF', 'TITLE', 'UL', 'OL', 'LI', 'DL', 'DLENTRY',
         'DLHEAD', 'DTHD', 'DT', 'DDHD', 'DD', 'ABSTRACT', 'SHORTDESC', 'TOPIC', 'BODY', 'PROLOG',
         'AUTHOR', 'COPYRIGHT', 'COPYRYEAR', 'COPYRHOLDER', 'METADATA', 'AUDIENCE', 'CATEGORY', );

    // stores parser state. We need to know whether we are within a certain (normally block) tag or not e.g. section
    private $inside_tag = array();

    // the actual XML parser (SAX) that parses the DITA XML file
    private $xml_parser = null;

    // the renderer instance e.g. instance of HTMLRenderer
    private $renderer = null;

    // constructor
    function __construct($renderer)        
    {
        Log::Debug(self::TAG, "__construct.");

        // Reset parser state: set $inside_tag elements to false
        foreach ($this->tags as $tag) {

            $this->inside_tag[$tag] = false;

        }

        $renderer->attach_parser($this); // the renderer needs to check parser state
        $this->renderer = $renderer; // the parser needs to callback on the renderer (these two are tightly coupled)
    }

    // destructor
    function __destruct()
    {
        Log::Debug(self::TAG, "__destruct.");
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                          PARSER STATE
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    // set value to true or false (only the parser itself calls this)
    private function set_inside_tag($tag, $value)
    {

        $this->inside_tag[$tag] = $value;

    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                          PARSER EVENT HANDLERS
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function startElementHandler($parser, $tag, $attributes)
    {
        unset($parser); // not used here

        // set tag state
        $this->set_inside_tag($tag, true);

        // then render
        $ret_status = false;
        $method_name = self::TAG_OPEN_METHOD_NAME . $tag; // note, method names not case sensitive

        // tag handlers return true if they handle a tag
        $ret_status = $this->renderer->$method_name($attributes);
        if (!$ret_status) {

            // TODO: add XML file name to this !!
            Log::Warn("Open tag not handled: $tag at line " . xml_get_current_line_number($this->xml_parser));

        }

    }

    private function endElementHandler($parser, $tag)
    {
        unset($parser); // not used here

        // NOTE: really important to render first, then set close tag state!

        $ret_status = false;
        $method_name = self::TAG_CLOSE_METHOD_NAME . $tag; // note: methods names not case sensitive

        $ret_status = $this->renderer->$method_name();
        if (!$ret_status) {

            // TODO: add XML file name to this !!
            Log::Warn("Close tag not handled: $tag at line ". xml_get_current_line_number($this->xml_parser));

        }

        // set tag state
        $this->set_inside_tag($tag, false);

    }

    private function characterDataHandler($parser, $cdata)
    {
        unset($parser); // not used here

        $this->renderer->handle_cdata($cdata);

    }

    private function defaultHandler($parser, $text)
    {
        unset($parser); // not used here

        $this->renderer->handle_default($text);

    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                          PUBLIC METHODS
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    /*
     * External code (such as the renderer) needs to know parser state. This method is used for that
     * return true or false if we are/are not inside tag
     */

    public function is_inside_tag($tag)
    {

        return $this->inside_tag[$tag];

    }


    /*
     * MAIN calling point - the external code calls this to kick off the whole process
     * parse and render, the rendered document is returned for writing
     */    
    public function parse($filename)
    {

        Log::Debug(self::TAG, "File '$filename' received.");

        // open file for reading
        $fh = fopen($filename, "r");

        if (!$fh){
            Log::Fatal(self::TAG, "Failed to open file '$filename'.");
            exit (-1);
        }


        // set up an XML parser
        $this->xml_parser = xml_parser_create("UTF-8");

        // allow parser to callback on event handlers contained in this object
        xml_set_object($this->xml_parser, $this);

        // set options as required
        xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($this->xml_parser, XML_OPTION_SKIP_WHITE, true);


        // set handlers
        xml_set_element_handler($this->xml_parser, 'startElementHandler', 'endElementHandler');
        xml_set_character_data_handler($this->xml_parser, 'characterDataHandler');
        xml_set_default_handler($this->xml_parser, 'defaultHandler');
        // TODO: there are other handlers we need to hook up here


        // process XML file - invoke renderer callbacks (which set state and call the renderer)
        $blockSize = 4 * 1024;
        while ($data = fread($fh, $blockSize)) {

            if (!xml_parse($this->xml_parser, $data, feof($fh))) {
                Log::Fatal(self::TAG, 'Parse error: ' .
                    xml_error_string(xml_get_error_code($this->xml_parser)) .
                    " at line " .
                    xml_get_current_line_number($this->xml_parser));
                exit(-1);
            }
        }

        xml_parser_free($this->xml_parser);

        // close input file
        fclose($fh);

        // return the rendered document
        return $this->renderer->get_document();

    }
    
}

?>