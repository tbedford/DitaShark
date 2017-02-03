<?php

// meta, othermeta
// dates/times should be stored in SQL format 
// YYYY-mm-dd HH:MM:SS
// 

class Audience {
    public $type;
    public $job;
    public $experiencelevel;
    public $name;
}

class Copyright {
    public static $copyryear;
    public static $copyrholder;
}

class Metadata {
    public static $audience = array(); // can be multiple audiences
    public static $category = array(); // can be multiple categories
    public static $data;
    public static $dataabout;
    public static $foreign;
    public static $keywords; // list of <keyword>s
    public static $othermeta;
    public static $prodinfo;
    public static $unknown;
}

class Prodinfo {
    public static $brand;
    public static $prodname;
    public static $prognum;
    public static $component;
    public static $featnum;
    public static $platform;
    public static $series;
    public static $vrmlist; // list of <vrm>s
}

class Critdates {
    public static $created;
    public static $revised;
}

class Prolog {
    // attributes, one for each prolog element
    public static $author;
    public static $permissions;
    public static $publisher;
    public static $resourceid;
    public static $source; 
}

?>