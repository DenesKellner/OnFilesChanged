<?php
main($argv);
exit;



function main($args) {
    error_reporting(E_ALL - E_NOTICE);

    global $Current;

    if(count($args)==1) die(help());

    arg("

        -l      --listfile          str        Listfile with patterns to watch (1 per line)
        -i      --idletext          str        Text to display in taskbar when watching
        -a      --actiontext        str        Text to display when dealing with changes
        -c      --countdowntext     str        Text to display when waiting for settledown
        -s      --settledown        str        Act only when settled down for n seconds
        -w      --wait              int        Wait n seconds between polls
        -n      --new-process       bool       Open a new process when reacting on changes

    ");

    $cmd = "watch";
    $phpCall = "cmd_$cmd"; if(!function_exists($phpCall)) die("Invalid command ($cmd)\n");
    $phpCall();
    exit;

 }
function help() {
    $text = "

        |
        |       onFilesChanged                                    (c) 2019, Denes Kellner
        |       --------------
        |
        |       Watches one or more file patterns (mydir/*.xxx) for changes, and executes
        |       a certain command when it happens. Optionally you can wait for a while to
        |       avoid too frequent reactions - this is called a settledown time, it means
        |       that after a change, there should be n seconds of non-changing to trigger
        |       the action. A very handy tool for continuous backups.
        |
        |       Syntax:
        |
        |           onfileschanged <command> <pattern1> <pattern2>
        |           onfileschanged <command> -l <listfile>
        |           onfileschanged quit -l <listfile>
        |
        |       Options:
        |
        |           -l, --listfile          A textfile with list of patterns to watch
        |                                   (one pattern per line, no magic)
        |           -i, --idletext          Text to display in taskbar when watching
        |           -a, --actiontext        Text to display when dealing with changes
        |           -c, --countdowntext     Text to display when waiting for settledown
        |           -w, --wait              Wait n seconds between polls
        |           -n, --new-process       Open a new process when reacting on changes
        |                                   (useful when you don't want to wait till it
        |                                   finishes)
        |           -s, --settledown        Act only when something has changed but then
        |                                   things settled down for n seconds. (Good for
        |                                   delayed action on a quick series of changes)
        |

    ";

    foreach(explode("\n",trim($text))as $x) print substr(ltrim(trim($x),"|"),3)."\n";

 }


function cmd_watch() {

    $command = arg(1);
    $oldTitle = cli_get_process_title();
    $settleTiming = arg("settledown");

    $patternList = "";
    $listfile = arg("listfile");
    if($listfile) if(file_exists($listfile)) $patternList = trim(file_get_contents($listfile))."\n";
    for($i=2;$i<99;++$i) {$x=arg($i);if(trim($x)) $patternList.="$x\n";}
    $patternList = strtr($patternList,"/","\\");
    $patternList = explode("\n",trim($patternList));
    $waitSeconds = arg("wait") ?: 1;

    $lastMoment = "";
    $settleCounter = 0;
    cli_set_process_title(arg("idletext","Watching"));

    print "Started watching the patterns below:\n\n -  ".join("\n -  ",$patternList)."\n\n";
    print "Press Ctrl+C or close window to stop\n";

    while(1) {// never-ending story. Press Ctrl+C or close window

        sleep($waitSeconds);
        $goExecute = 0;

        $thisMoment = "";
        foreach($patternList as $p) if(trim($p)) $thisMoment.=listFiles($p)."\n\n";
        if(!$lastMoment) $lastMoment = $thisMoment; // don't react on first cycle

        $changed = ($thisMoment <> $lastMoment); // what a line!
        $lastMoment = $thisMoment;

        if(!$changed) if(!$settleCounter) continue;
        if(!$changed) {
            cli_set_process_title(arg("countdowntext","Waiting ...")." ".$settleCounter);
            if(0<--$settleCounter) continue;
            $goExecute = 1;
        }else{
            if(!$settleTiming) $goExecute = 1;
            if( $settleTiming) $goExecute = 0;
            $settleCounter = $settleTiming;
        }

        if($goExecute) {
            if($command==="quit") {
                cli_set_process_title($oldTitle);
                exit;
            }
            cli_set_process_title(arg("actiontext","Reacting on changes")); launch($command); sleep(1);
            cli_set_process_title(arg("idletext","Watching"));
        }

    }

}


function listFiles($pattern) {
    $files = glob($pattern);
    $out = "";
    foreach($files as $f) $out .= sprintf("%s,%s,%s\n",$f,filemtime($f),filesize($f));
    return $out;
}

function launch($command) {
    if(arg("new-process")) $command = 'start "" '.$command;
    pclose(popen($command,'r'));
}


//---------- cross-project functions -----------------------------------------------------------------------------------------------------------------------------

    function contains       ($haystack,$needle,$offset=0) {$x=@strpos($haystack,$needle,$offset);if($x===false)return 0;return 1;}
    function preg_parse     ($regex,$subject) {
        $x = preg_match($regex,$subject,$a);
        if(!$x) $a=[];
        array_shift($a);
        return $a;
    }

    function arg($x="",$default=null) {

        static $arginfo = [];

        /* helper */ $contains = function($h,$n) {return (false!==strpos($h,$n));};
        /* helper */ $valuesOf = function($s) {return explode(",",$s);};

        //  called with a multiline string --> parse arguments
        if($contains($x,"\n")) {

            //  parse multiline text input
            $args = $GLOBALS["argv"] ?: [];
            $rows = preg_split('/\s*\n\s*/',trim($x));
            $data = $valuesOf("char,word,type,help");
            foreach($rows as $row) {
                list($char,$word,$type,$help) = preg_split('/\s\s+/',$row);
                $char = trim($char,"-");
                $word = trim($word,"-");
                $key  = $word ?: $char ?: ""; if($key==="") continue;
                $arginfo[$key] = compact($data);
                $arginfo[$key]["value"] = null;
            }

            $nr = 0;
            while($args) {

                $x = array_shift($args); if($x[0]<>"-") {$arginfo[$nr++]["value"]=$x;continue;}
                $x = ltrim($x,"-");
                $v = null; if($contains($x,"=")) list($x,$v) = explode("=",$x,2);
                $k = "";foreach($arginfo as $k=>$arg) if(($arg["char"]==$x)||($arg["word"]==$x)) break;
                $t = $arginfo[$k]["type"];
                switch($t) {
                    case "bool" : $v = true; break;
                    case "str"  : if(is_null($v)) $v = array_shift($args); break;
                    case "int"  : if(is_null($v)) $v = array_shift($args); $v = intval($v); break;
                }
                $arginfo[$k]["value"] = $v;

            }

            return $arginfo;

        }

        //  called with a question --> read argument value
        if($x==="") return $arginfo;
        if(isset($arginfo[$x]["value"])) return $arginfo[$x]["value"];
        return $default;

    }

//----------------------------------------------------------------------------------------------------------------------------------------------------------------


