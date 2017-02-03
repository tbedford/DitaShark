#!/usr/bin/env php

<?php

function get_base ($filename)
{
    $parts = pathinfo($filename);
    return $parts['dirname'];
}

function relative_to_doc_root ($base, $filename)
{
    return $base . '/' . $filename;    
}

echo "cwd: ". getcwd() ."\n";
chdir("./");
echo "cwd: ". getcwd() ."\n";


$filename1 = "./subdir/test1.ditamap";
$filename2 = "./ditamaps/subdir1/subdir2/test1.ditamap";
$filename3 = "../../subdir3/test1.ditamap";
$filename4 = "fred.ditamap";

// load ditamap
// then set base for contained references
$ditamap_base = get_base ($filename1);

// references in this file are now relative to ditamap_base
// so prepend that on filenames e.g.:
$fn = relative_to_doc_root ($ditamap_base, $filename4);
echo "file-> $fn\n";

$fn = relative_to_doc_root ($ditamap_base, $filename3);
echo "file-> $fn\n";


// gives you the real absolute path to the specified file
$rp = realpath($filename1);
if ($rp){
    echo "RP: " . $rp . "\n";
}
else
{
    echo "File does not exist!\n";
}
?>
