<?php

require_once './classes/Parser.php';
require_once './classes/MapReader.php';
require_once './classes/Config.php';
require_once './classes/FileWriter.php';
require_once './classes/HTMLRenderer.php';
require_once './classes/Log.php';

const TAG ='Shark';

$ditafiles = array();

// load config from build file -- config file contains all information required to build docs

// FIXME - INI/Build file needs to be passed in via command line

$buildfile = "sample.ini";
Config::$config = parse_ini_file($buildfile); 
if (Config::$config == false)
{
    Log::Fatal(TAG, "Failed to load configuration from build file '$buildfile'");
    exit (-1);
}

$doc_root = getcwd(); // save where we are
Config::$config['doc_root'] = $doc_root;

$reader = new MapReader();
$ditafiles = $reader->start($ditamap);

chdir($doc_root); // make sure we go back to the doc root

// select a renderer based on output type requested
$render_type = strtoupper($render_type); // we will compare on upper case
switch ($render_type) {
case 'HTML':
    $renderer = new HTMLRenderer();
    $writer = new FileWriter();
    break;
    
case 'PDF':
    //$renderer = new PDFRenderer($this);
    break;
    
case 'DATABASE':
    //$renderer = new DatabaseRenderer($this);
    break;

default:
    Log::Fatal(TAG, "That output type is not supported, so cannot create renderer.");
    exit (-1);
    break;
    
}

$parser = new Parser($renderer);

foreach ($ditafiles as $ditafile){
    $document = $parser->parse($ditafile);
    $renderer->clear();
    $writer->write($ditafile, $document); // write document out, note ditafile is the input file as we need this to work out the output file name

}

?>