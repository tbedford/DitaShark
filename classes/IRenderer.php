<?php
/**
 */

interface IRenderer {

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                MAIN METHODS
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function get_document(); // returns the rendered document
    public function attach_parser($parser);
    public function clear(); // clears the HTML document

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                EVENT HANDLERS (other than open/close tag)
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function handle_cdata ($cdata);
    public function handle_default ($text);

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //                TAG HANDLERS
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////


   // PROLOG
    public function handle_open_prolog($attributes);
    public function handle_close_prolog();

    // TOPIC
    public function handle_open_topic($attributes);
    public function handle_close_topic();

    // CONCEPT
    public function handle_open_concept($attributes);
    public function handle_close_concept();

    // TASK
    public function handle_open_task($attributes);
    public function handle_close_task();

    // REFERENCE
    public function handle_open_reference($attributes);
    public function handle_close_reference();

    // BODY
    public function handle_open_body($attributes);
    public function handle_close_body();

    // CONBODY
    public function handle_open_conbody($attributes);
    public function handle_close_conbody();

    // P
    public function handle_open_p($attributes);
    public function handle_close_p();

    // B
    public function handle_open_b($attributes);
    public function handle_close_b();

    // I
    public function handle_open_i($attributes);
    public function handle_close_i();

    // SIMPLETABLE
    public function handle_open_simpletable($attributes);
    public function handle_close_simpletable();

    // STHEAD
    public function handle_open_sthead($attributes);
    public function handle_close_sthead();

    // STROW
    public function handle_open_strow($attributes);
    public function handle_close_strow();

    // STENTRY
    public function handle_open_stentry($attributes);
    public function handle_close_stentry();

    // XREF
    public function handle_open_xref($attributes);
    public function handle_close_xref();

    // SECTION
    public function handle_open_section($attributes);
    public function handle_close_section();

    // TITLE
    public function handle_open_title($attributes);
    public function handle_close_title();

    // CODEBLOCK
    public function handle_open_codeblock($attributes);
    public function handle_close_codeblock();

    // UL
    public function handle_open_ul($attributes);
    public function handle_close_ul();

    // OL
    public function handle_open_ol($attributes);
    public function handle_close_ol();

    // LI
    public function handle_open_li($attributes);
    public function handle_close_li();

    // DL
    public function handle_open_dl($attributes);
    public function handle_close_dl();

    // DLENTRY
    public function handle_open_dlentry($attributes);
    public function handle_close_dlentry();

    // DLHEAD
    public function handle_open_dlhead($attributes);
    public function handle_close_dlhead();

    // DTHD
    public function handle_open_dthd($attributes);
    public function handle_close_dthd();

    // DT
    public function handle_open_dt($attributes);
    public function handle_close_dt();

    // DDHDH
    public function handle_open_ddhd($attributes);
    public function handle_close_ddhd();

    // DD
    public function handle_open_dd($attributes);
    public function handle_close_dd();

    // ABSTRACT
    public function handle_open_abstract($attributes);
    public function handle_close_abstract();

    // SHORTDESC
    public function handle_open_shortdesc($attributes);
    public function handle_close_shortdesc();

    // other tags ...
} 