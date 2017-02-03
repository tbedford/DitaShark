<?php

/**
 * HTML RENDERER
 * This is the thing that generates actual HTML!
 * It implements the IRenderer interface to make sure that all supported tags are handled. The PDFRenderer
 * or the DatabaseRenderer / Connector would also need to implement the IRenderer interface too.
 * To generate HTML correctly we need to know about parser state, so a parser instance is passed in
 * via the constructor, so we can its public methods, for example to check state or obtain the filename of the
 * file we are processing.
 * The renderer contains methods for handling the opening and closing of specific tags (handle_open_tag and
 * handle_close_tag). This approach was thought better than the two big switch statements
 * that existed before! Most of the real work is done in the tag handlers.
 * NOTE: we only build an HTML document, we don't know anything about writing it out in here. At the end of the rendering process
 * the parser will write the data out (or should the parser pass back the generated document?).
 */

require_once 'Parser.php';
require_once 'Log.php';
require_once 'IRenderer.php';
require_once 'Prolog.php';

class HTMLRenderer implements IRenderer
{

    const TAG = 'HTMLRenderer';

    private $parser; // store the parser object inside instance data for convenience (so we don't need to pass
                     // in on each handler method)

    private $document = ""; // the HTML document being built

    private $topic_id; // store for use in anchor generation


    // parser is hooked in as we need to interrogate parser state e.g. inside p?
    function __construct()
    {
        Log::Debug(self::TAG, "__construct");

    }

    function __destruct()
    {
        Log::Debug(self::TAG, "__destruct");
    }

    public function attach_parser($parser)
    {
        $this->parser = $parser;        
    }


    public function get_document ()
    {
        return $this->write_header() . $this->document . $this->write_footer();
    }

    public function clear ()
    {
        $this->document = "";
    }
    
    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                PRIVATE HELPER METHODS
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    const JOIN_CHAR = '_';

    /**
     * Simply creates an anchor point. The ID passed in must be the globally unique
     * combination of topic_id and elem_id
     * format is: <A NAME="***"></A>
     */
    private function generate_anchor_point($topic_id, $elem_id)
    {
        // not all block elems will have been assigned an ID in DITA source
        // we put the check in this function simply to make the code
        // easier to read. The cost is the expense of a function call
        // where one might not have been required.
        if ($elem_id == "" or $elem_id == null)
        {
            return;
        }
        $this->write_out('<a id="' . $topic_id . self::JOIN_CHAR . $elem_id . '"></a>');
    }

    private function generate_unique_anchor($xref_anchor_part)
    {
        $subparts = explode('/', $xref_anchor_part);
        if ($subparts[1] == ''){
            return '#'.$subparts[0];
        }
        return '#'.$subparts[0].self::JOIN_CHAR.$subparts[1];
    }

    /**
     * Takes a path that includes a filename with a .dita or .xml extension, or other
     * extension, and changes the extension to .html. This is part of code to convert
     * xrefs to <a href=> constructs. That is linking between and within HTML
     * documents. Returns the path with the file extension changed.
     */
    private function change_file_extension ($path)
    {
        $path_parts = pathinfo($path);
        $filename = $path_parts['filename'];
        $dirname = $path_parts['dirname'];

        return $dirname ."/".$filename.".html";
    }


    /**
     * MAIN FUNCTION (calls helpers)
     * Converts an xref href to an href suitable for use in an anchor tag in HTML <a>
     * NOTE: we actually only need to parse the href as the XML parser extracts that for us!!
     * There are FIVE formats we need to handle:
     * TYPE 1 -- href="../client_api/test.dita/references/workflow.xml"
     * TYPE 2 -- href="../client_api/test.dita/references/workflow.xml#topic_id"
     * TYPE 3 -- href="../client_api/test.dita/references/workflow.xml#topic_id/elem_id"
     * or links within file:
     * TYPE 4 -- href="#topic_id"
     * TYPE 5 -- href="#topic_id/elem_id"
     *
     * Note that where you have #/topicID/elementID these two parts need to be combined to create a single unique
     * anchor within the HTML document. For example you could create TopicID_elementID. The reason for this is
     * TopicIDs must be unique in the file (and ideally globally), whereas element Ids only need to be unique within a
     * topic. The combination of the two creates a globally unique anchor. Note the reason topics officially (see the spec)
     * only need to be file unique is that in xrefs the full filename is usually specified to qualify the link - in the directory
     * linked to there will only be one file with that name.
     *
     * "../client_api/test.dita/references/workflow.dita#topic_id/elem_id"
     * This function takes the part to the right of the '#' as input.
     * It separates into two parts based on '/'. It then combines these to form a
     * globally unique ID that can be used as an anchor ID in an anchor <a> tag.
     * xrefs use the combination of topic_id (which is file unique) and elem_id (which is only
     * topic unique) to provide a unique identifier. Note, I tend to try and make my topic IDs and
     * Element IDs globally unique, by using a namespace. I also think it's best practice
     * to confine one topic to one file. Assumes '/' between topic_id and elem_id
     * as per the xref spec. Note if topic id only is used there will be no '/'.
     */

    private function convert_xref_href($href)
    {
        $parts = explode('#', $href);
        if ($parts[1]=="")
        {
            // Type 1
            return $this->change_file_extension($href);

        }
        elseif ($parts[0]=="")
        {
            // Type 4 or 5
            return $this->generate_unique_anchor($parts[1]);
        }
        else
        {
            // Type 2 or 3
            $newpart0 = $this->change_file_extension($parts[0]);
            $newpart1 = $this->generate_unique_anchor($parts[1]);
            return $newpart0 . $newpart1;
        }
    }

    // Just write HTML to a big 'document' string, we don't actually write to a file - the file writer does that
    private function write_out($text)
    {
        $this->document = $this->document . $text;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                HTML header, Meta and footer functions (help construct an HTML page)
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function build_meta ()
    {
        $meta = "\n<meta name='author' content='" . Prolog::$author . "'/>\n";
        $meta = $meta . "<meta name='copyrightyear' content='" . Copyright::$copyryear . "'/>\n";
        $meta = $meta . "<meta name='copyrightholder' content='" . Copyright::$copyrholder . "'/>\n";

        foreach (Metadata::$audience as $audience){
            $meta = $meta . "<meta name='type' content='" . $audience->type . "'/>\n";
            $meta = $meta . "<meta name='job' content='" . $audience->job . "'/>\n";
            $meta = $meta . "<meta name='experiencelevel' content='" . $audience->experiencelevel . "'/>\n";
        }

        // TODO: add category meta
        foreach (Metadata::$category as $category){
            $meta = $meta . "<meta name='category' content='" . $category . "'/>\n";
        }


        return $meta;
    }

    public function write_header()
    {
        $head_start = <<< EOF
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<title>Parse Test - with syntax highlighting</title>
EOF;

        $syntax_highlight = <<< EOF

<!-- SYNTAX HIGHLIGHTING : START  -->

<!-- TODO: Maybe replace by Geshi? -->

<!-- Include required JS files -->
<script type="text/javascript" src="../highlighter/js/shCore.js"></script>

<!--
    At least one brush, here we choose Java. You need to include a brush for every
    language you want to highlight
-->
<script type="text/javascript" src="../highlighter/js/shBrushJava.js"></script>
<script type="text/javascript" src="../highlighter/js/shBrushPhp.js"></script>

<!-- Include *at least* the core style and default theme -->
<link href="../highlighter/css/shCore.css" rel="stylesheet" type="text/css" />
<link href="../highlighter/css/shThemeDefault.css" rel="stylesheet" type="text/css" />

<!-- You would probably want to allow the user to select a dark theme if preferred
<link href="../highlighter/css/shCoreMidnight.css" rel="stylesheet" type="text/css" />
-->

<!-- Finally, to actually run the highlighter, you need to include this JS on your page -->
<script type="text/javascript">
     SyntaxHighlighter.all()
</script>

<!-- SYNTAX HIGHLIGHTING : END  -->
EOF;

        $style_sheets = '<link rel="stylesheet" type="text/css" href="' . Config::$config['stylesheet'] . '"/>';

        $meta = $this->build_meta(); // build metadata string

        $head_end = <<< EOF
</head>
<body>
EOF;
        
        return $head_start . $syntax_highlight . $style_sheets . $meta . $head_end . "\n"; // \n to make things a bit neater 

    }

    public function write_footer()
    {
        // TODO: add "built with" message in here, include version, build date/time etc.
        
        $footer = "<div class='footer'><hr/><p><b>Built with DitaShark!</b></p></div>" . "\n";
        $footer = $footer . '</body></html>' . "\n";

        return ($footer);

    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                EVENT HANDLERS (other than open/close tag)
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    /* Handles cdata */
    public function handle_cdata ($cdata)
    {

        // deal with prolog/metadata elements

        if ($this->parser->is_inside_tag('AUTHOR'))
        {
            // set author in Prolog
            Prolog::$author = $cdata;
            return;
        }
        
        
        if ($this->parser->is_inside_tag('COPYRHOLDER'))
        {
            // set copyrholder in Prolog
            Copyright::$copyrholder = $cdata;
            return;
        }

        if ($this->parser->is_inside_tag('CATEGORY'))
        {
            Metadata::$category[] = $cdata; // push new cateory object onto array
            return;
        }

        // else write out data
        $this->write_out (htmlentities($cdata)); // escape chars to entities FIXME: Not sure this handles UTF-8 correctly

    }

    /* handles anything else */
    public function handle_default ($text)
    {
        $this->write_out ("$text\n");
    }


    /*
     * TODO: there are some other special handlers that need to go in here!!
     *
     */


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                TAG HANDLERS
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    // CATEGORY

    public function handle_open_category($attributes)
    {
        return true;
    }

    public function handle_close_category()
    {

        return true;
    }

    // METADATA

    public function handle_open_metadata($attributes)
    {
        return true;
    }

    public function handle_close_metadata()
    {

        return true;
    }

    // AUDIENCE
    public function handle_open_audience($attributes)
    {
        $audience = new Audience();

        if (strtoupper($attributes['TYPE']) == "OTHER"){
            $audience->type = $attributes['OTHERTYPE'];
        }
        else {
            $audience->type = $attributes['TYPE'];    
        }

        if (strtoupper($attributes['JOB']) == "OTHER"){
            $audience->job = $attributes['OTHERJOB'];
        }
        else {
            $audience->job = $attributes['JOB'];    
        }
        
        $audience->experiencelevel = $attributes['EXPERIENCELEVEL'];

        Metadata::$audience[] = $audience; // push object onto list of audience objects without function call

        return true;
    }

    public function handle_close_audience()
    {

        return true;
    }

    // COPYRIGHT

    public function handle_open_copyright($attributes)
    {
        return true;
    }

    public function handle_close_copyright()
    {

        return true;
    }

    // COPYRYEAR (year is in attribute)

    public function handle_open_copyryear($attributes)
    {
        Copyright::$copyryear = $attributes['YEAR'];
        return true;
    }

    public function handle_close_copyryear()
    {

        return true;
    }

    // COPYRHOLDER

    public function handle_open_copyrholder($attributes)
    {
        return true;
    }

    public function handle_close_copyrholder()
    {

        return true;
    }


    // AUTHOR

    public function handle_open_author($attributes)
    {
        return true;
    }

    public function handle_close_author()
    {

        return true;
    }


    // PROLOG

    public function handle_open_prolog($attributes)
    {
        return true;
    }

    public function handle_close_prolog()
    {
        return true;
    }

    // CONCEPT

    /*    concept must have ID, store it, as we need to use
        this in some case to generate a unique ID for elements
        This shouldn't happen but if the concept doesn't have
        an ID everything will go badly wrong*/

    public function handle_open_concept($attributes)
    {
        $id = $attributes['ID'];
        if ($id == "" or $id == null) {
            Log::Fatal(self::TAG, "Topic MUST have a unique ID. No ID was specified for this concept.");
            exit(-1); // TODO: we need proper error code numbering system
        }
        $this->topic_id = $id; // store topic_id for later use in generating unique anchors for elems

        $this->write_out('<a id="' . $id . '"></a>'); // was name, but name deprectaed in XHTML 1.0 strict

        return true;
    }

    public function handle_close_concept()
    {

        return true;
    }

    // TASK

    public function handle_open_task($attributes)
    {

        $id = $attributes['ID'];
        if ($id == "" or $id == null) {
            Log::Fatal(self::TAG, "Topic MUST have a unique ID. No ID was specified for this task");
            exit(-1); // TODO: we need proper error code numbering system
        }
        $this->topic_id = $id; // store topic_id for later use in generating unique anchors for elems

        $this->write_out('<a id="' . $id . '"></a>'); // was name, but name deprectaed in XHTML 1.0 strict

        return true;
    }

    public function handle_close_task()
    {

        return true;
    }

    // REFERENCE

    public function handle_open_reference($attributes)
    {
        $id = $attributes['ID'];
        if ($id == "" or $id == null) {
            Log::Fatal(self::TAG, "Topic MUST have a unique ID. No ID was specified for this reference.");
            exit(-1); // TODO: we need proper error code numbering system
        }
        $this->topic_id = $id; // store topic_id for later use in generating unique anchors for elems

        $this->write_out('<a id="' . $id . '"></a>'); // was name, but name deprectaed in XHTML 1.0 strict

        return true;
    }

    public function handle_close_reference()
    {

        return true;
    }

    // TOPIC

    public function handle_open_topic($attributes)
    {
        $id = $attributes['ID'];
        if ($id == "" or $id == null) {
            Log::Fatal(self::TAG, "Topic MUST have a unique ID. No ID was specified for this reference.");
            exit(-1); // TODO: we need proper error code numbering system
        }
        $this->topic_id = $id; // store topic_id for later use in generating unique anchors for elems

        $this->write_out('<a id="' . $id . '"></a>'); // was name, but name deprectaed in XHTML 1.0 strict
       
        return true;
    }

    public function handle_close_topic()
    {

        return true;
    }

    // BODY

    public function handle_open_body($attributes)
    {
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out('<div class="body">');

        return true;
    }

    public function handle_close_body()
    {

        $this->write_out ("</div>");

        return true;
    }

    // CONBODY

    public function handle_open_conbody($attributes)
    {
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out('<div class="conbody">');

        return true;
    }

    public function handle_close_conbody()
    {

        $this->write_out ("</div>");

        return true;
    }


    // P

    public function handle_open_p($attributes)
    {
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ("<p>");

        return true;
    }

    public function handle_close_p()
    {

        $this->write_out ("</p>");

        return true;
    }

    // B

    public function handle_open_b($attributes)
    {

        unset($attributes); // not used here

        $this->write_out ("<b>");

        return true;
    }

    public function handle_close_b()
    {

        $this->write_out ("</b>");

        return true;
    }

    // I

    public function handle_open_i($attributes)
    {
        unset($attributes); // not used here

        $this->write_out ("<i>");
        return true;
    }

    public function handle_close_i()
    {

        $this->write_out ("</i>");

        return true;
    }

    // SIMPLETABLE

    public function handle_open_simpletable($attributes)
    {
       /*
        * Sometimes in DITA someone will put a table inside a para. We can't just
        * emit these as in XHTML you can't have a table (or other block structure)
        * inside a para (<p>). So the solution is to close the current p,
        * emit the table, and open a new p. Note the table closing code is the
        * code that opens the new <p>. Note this might give <p></p> constructs
        * that is - empty paras. This is something that HTML Tidy can remove in
        * the post processing stage (along with pretty print, validation etc.)
        *
        */
        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("</p>\n"); // close current para
        }
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ("<table>");

        return true;
    }

    public function handle_close_simpletable()
    {

        $this->write_out ("</table>\n");
        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("<p>");
        }

        return true;
    }

    // STHEAD

    public function handle_open_sthead($attributes)
    {
        unset($attributes); // not used here

        $this->write_out ("<tr>");

        return true;
    }

    public function handle_close_sthead()
    {

        $this->write_out ("</tr>");

        return true;
    }

    // STROW

    public function handle_open_strow($attributes)
    {
        unset($attributes); // not used here

        $this->write_out ("<tr>");

        return true;
    }

    public function handle_close_strow()
    {

        $this->write_out ("</tr>");

        return true;
    }

    // STENTRY

    public function handle_open_stentry($attributes)
    {
        unset($attributes); // not used here

        // if we are inside an sthead need to generate <th>
        if ($this->parser->is_inside_tag('STHEAD'))
            $this->write_out ("<th>");
        else
            $this->write_out ("<td>");

        return true;
    }

    public function handle_close_stentry()
    {

        // if we are inside an sthead need to generate <th>
        if ($this->parser->is_inside_tag('STHEAD'))
            $this->write_out ("</th>");
        else
            $this->write_out ("</td>");

        return true;
    }

    // XREF

    public function handle_open_xref($attributes)
    {
        // if the link scope is external use an anchor tag (<a...>...</a>)
        if ($attributes['SCOPE'] == 'external') {
            $this->write_out ('<a href="');
            $this->write_out ($attributes['HREF']);
            $this->write_out ('">');
        } // peer or local or not specified (at least, not external)
        else {
            $this->write_out ('<a href="');
            $this->write_out ($this->convert_xref_href($attributes['HREF']));
            $this->write_out ('">');
        }

        return true;
    }

    public function handle_close_xref()
    {

        $this->write_out ("</a>");

        return true;
    }

    // SECTION

    public function handle_open_section($attributes)
    {
        // create a div class = section
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ('<div class="section">');

        return true;
    }

    public function handle_close_section()
    {

        $this->write_out ("</div>");

        return true;
    }

    // TITLE

    public function handle_open_title($attributes)
    {
        unset($attributes); // not used here

        // we might be in a topic or a section (or something else)
        // TODO: handle titles in other locations
        if ($this->parser->is_inside_tag('SECTION')) {
            $this->write_out ('<h2 class="sectiontitle">');
        } else {
            $this->write_out ('<h1 class="title">');
        }

        return true;
    }

    public function handle_close_title()
    {

        if ($this->parser->is_inside_tag('SECTION')) {
            $this->write_out ("</h2>");
        } else {
            $this->write_out ("</h1>");
        }

        return true;
    }

    // CODEBLOCK

    public function handle_open_codeblock($attributes)
    {

        $brush = $attributes['OUTPUTCLASS'];
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        if ($brush != "" and $brush != null) {
            $this->write_out ('<pre class="brush: ' . "$brush" . '">');
        } else {
            // no brush
            $this->write_out ('<pre>');
        }

        return true;
    }

    public function handle_close_codeblock()
    {

        $this->write_out ("</pre>");

        return true;
    }

    // UL

    public function handle_open_ul($attributes)
    {

        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("</p>");
        }
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ("<ul>");


        return true;
    }

    public function handle_close_ul()
    {

        $this->write_out ("</ul>");
        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("<p>");
        }

        return true;
    }

    // OL

    public function handle_open_ol($attributes)
    {

        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("</p>");
        }
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ("<ol>");

        return true;
    }

    public function handle_close_ol()
    {

        $this->write_out ("</ol>");
        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("<p>");
        }

        return true;
    }

    // LI

    public function handle_open_li($attributes)
    {

        unset($attributes); // not used here

        $this->write_out ("<li>");

        return true;
    }

    public function handle_close_li()
    {

        $this->write_out ("</li>");

        return true;
    }

    // DL

    public function handle_open_dl($attributes)
    {
        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("</p>");
        }
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ("<dl>");

        return true;
    }

    public function handle_close_dl()
    {

        print ("</dl>");
        if ($this->parser->is_inside_tag('P')) {
            $this->write_out ("<p>");
        }


        return true;
    }

    // DLENTRY

    public function handle_open_dlentry($attributes)
    {
        unset($attributes); // not used here

        // we don't really to output anything for this - although we could emit a <div> for styling purposes

        return true;
    }

    public function handle_close_dlentry()
    {

        // we don't really need to output anything for this - although we could emit a <div> for styling purposes

        return true;
    }

    // DLHEAD

    public function handle_open_dlhead($attributes)
    {
        unset($attributes); // not used here

        // we don't really need to output anything for this - although we could emit a <div> for styling purposes

        return true;
    }

    public function handle_close_dlhead()
    {

        // we don't really need to output anything for this - although we could emit a <div> for styling purposes

        return true;
    }

    // DTHD

    public function handle_open_dthd($attributes)
    {
        unset($attributes); // not used here

        $this->write_out ('<dt id="dthd">');

        return true;
    }

    public function handle_close_dthd()
    {

        $this->write_out ("</dt>");

        return true;
    }

    // DT

    public function handle_open_dt($attributes)
    {

        unset($attributes); // not used here

        $this->write_out ("<dt>");

        return true;
    }

    public function handle_close_dt()
    {

        $this->write_out ("</dt>");

        return true;
    }

    // DDHDH

    public function handle_open_ddhd($attributes)
    {
        unset($attributes); // not used here

        $this->write_out ('<dd id="ddhd">');

        return true;
    }

    public function handle_close_ddhd()
    {
        $this->write_out ("</dd>");

        return true;
    }

    // DD

    public function handle_open_dd($attributes)
    {
        unset($attributes); // not used here

        $this->write_out ("<dd>");


        return true;
    }

    public function handle_close_dd()
    {

        $this->write_out ("</dd>");

        return true;
    }

    // ABSTRACT

    public function handle_open_abstract($attributes)
    {
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ('<div class="abstract">');

        return true;
    }

    public function handle_close_abstract()
    {

        $this->write_out ("</div>");

        return true;
    }

    // SHORTDESC

    public function handle_open_shortdesc($attributes)
    {
        $this->generate_anchor_point($this->topic_id, $attributes['ID']);
        $this->write_out ('<span class="shortdesc">');

        return true;
    }

    public function handle_close_shortdesc()
    {

        $this->write_out ("</span>");

        return true;
    }

} 