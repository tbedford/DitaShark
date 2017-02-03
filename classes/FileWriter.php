<?php

require_once 'IWriter.php';
require_once 'Config.php';

class FileWriter implements IWriter {

    const TAG = 'FileWriter';

    private $output_dir; // the directory for output HTML files

    private $doc_root; // the directory in which the script was run 

    function __construct ()
    {

        $output_dir = Config::$config['output_dir'];

        Log::Info(self::TAG, "Output dir set to: $output_dir");

        // Set the default output directory name if required. 
        // The default output directory is 'output' - this can be overriden via command line options
        if ($output_dir == null or $output_dir == "")
        {
            $output_dir = "output"; 
        }
        

        $this->output_dir = $output_dir;
        $this->doc_root = Config::$config['doc_root'];

    }

    /*
     * The output file name is base + doc_root + output dir + path + filename
     *
     */

    private function create_output_file ($doc_root, $output_dir, $filename )
    {
        // first change the filename extension to .html
        $parts = pathinfo($filename);
        $filename_part = $parts['filename'];
        $filename = $parts['dirname'].'/'.$filename_part.'.html';

        // we need to make sure leading and trailing slashes are removed from output path
        $output_dir = trim($output_dir, '/');

        // Now find end of doc_root and interject output dir name
        $pattern = '@('.$doc_root.')(.*)@'; // use '@' in regexp to avoid clash with '/' in filepaths
        preg_match($pattern, $filename, $matches);
        $path_part = $matches[2]; // we want the second part of the match i.e. the bit that did not match
        $path_part = trim($path_part, '/'); // make sure we strip any leading or trailing slashes

        // build and return output filename
        $output_file = $doc_root.'/'.$output_dir.'/'.$path_part;

        // Create the output path as required
        $path_parts = pathinfo($output_file);
        $pathname = $path_parts['dirname'];

        Log::Debug(self::TAG, "Checking for directory '$pathname'");
        if(!file_exists($pathname)){
            Log::Info(self::TAG, "Directory '$pathname' does not exist. Attempting to create.");
            if(!mkdir($pathname, 0777, true)){
                Log::Fatal(self::TAG, "Failed to create '$pathname' directory!");
                exit(-1);
            }
            else {
                Log::Info(self::TAG, "Directory '$pathname' created!");
            }
        }
        else {
            Log::Info(self::TAG, "Directory '$pathname' already exists!");
        }

        // Now the directories have been created we can open file for writing      
        Log::Debug(self::TAG, "Attempt to open file '$output_file' for writing.");
        $fh_out = fopen($output_file, 'w'); // returns FALSE on fail
        if (!$fh_out){
            Log::Fatal(self::TAG, "Attempt to open file '$output_file' failed.");
            exit(-1);
        }
        return $fh_out; // save this instance for other methods as required e.g. to close file, write to file

    }


    // the renderer just builds a document string, this writes it out
    public function write ($filename, $document)
    {
        Log::Debug(self::TAG, "write: $filename");
        $fh_out = $this->create_output_file($this->doc_root, $this->output_dir, $filename); 
        fwrite($fh_out, $document);
        fclose($fh_out);
    }

    
}

?>