#!/usr/bin/php
<?php
$title = <<<EOT
ffsb v1.0 - ffmpeg batch stabilizer script
==========================================

EOT;
$helptext = <<<EOT
Usage
-----
$argv[0] <options> <settings> <filenames>

<filename>	file or directory to stabilize
<option>
	-h			display help
	-v <num>	verbose mode, 0=quiet, 1=basic, 2=detailed, 3=with ffmpeg progress
	-o			overwrite existing results
	-r			recurse subdirectories (if an input directory is given)
	-k			keep temporary files
	-x <ext>	output file extension 
	-s <file>	use stabilize settings from this file (default ./ffstb.set is used)
	-f <path>	name of the ffmpeg command. Default is ffmpeg
	-t <num>	maximum number of threads (not ready)
	-p <pattern> RegEx pattern to specify input filenames
	
Settings in .set file
---------------------
Settings are arguments in variable=value form.
Global default settings may be set in ffstb.set in the directory of the script.
Settings for an input directory may be overridden with an ffstb.set file in it.
Lines or line endings beginning with # are comments.

EOT;
$settingsheader = <<<EOT
The available settings with current global defaults are:

EOT;
const SET_SCRIPT=0;
const SET_DETECT=1;
const SET_TRANSFORM=2;
const SET_FFMPEG=3;
const SET_USER=4;		// User defined options
const SET_HIDDEN=5;
$settings = array(
	'help' => array(SET_SCRIPT, false, 'Display help && settings'),
	'verbose' => array(SET_SCRIPT, 1, 'Display info & progress. 0=quiet, 1=basic (default), 2=detailed'),
	'overwrite' => array(SET_SCRIPT, false, 'Overwrite existing output. May be overridden with -o in command line'),
	'keep' => array(SET_SCRIPT, false, 'Keep temporary files. May be overridden with -k in command line'),
	'recurse' => array(SET_SCRIPT, false, 'Recurse subdirectories. May be overridden with -r in command line'),
	'xout' => array(SET_SCRIPT, 'mp4', 'Output file extension. May be overridden with -x in command line'),
	'setfile' => array(SET_SCRIPT, '', 'Use in command line as -s filename: use stabilize settings from this file (default ./ffstb.set is used)'),
	'ffmpeg' => array(SET_SCRIPT, 'ffmpeg', 'name of ffmpeg command. May be overridden with -f in command line'),
	'exts' => array(SET_SCRIPT, 'avi,m2t,mov,mpg,mpeg,mp2,mp4,mts', 'Extensions to process in directories. May be overridden with -e in command line'),
	'threads' => array(SET_SCRIPT, 3, 'Maximum number of paralel threads (if multiple files are processed) May be overridden with -t in command line'),
	'trf' => array(SET_SCRIPT, 'trf', 'Extension of temporary move detection file'),
	'pattern' => array(SET_SCRIPT, '', 'RegEx pattern to specify additional input filenames (applied only on current directory, not recursive)'),

	'stepsize' => array(SET_DETECT, 6,	'Set stepsize of the search process.'),
	'shakiness' => array(SET_DETECT, 8,	'Set the shakiness of input video or quickness of camera. (1-10))'),
	'accuracy' => array(SET_DETECT, 9,	'Set the accuracy of the detection process. Range is 1-15; 1 is the lowest.'),

	'zoom' => array(SET_TRANSFORM, 0, 'Set percentage to zoom. A positive value will result in a zoom-in effect. 0=noo zoom.'),
	'optzoom' => array(SET_TRANSFORM, 'default', 'Set optimal zooming to avoid blank-borders. 0:disabled, 1=optimal, 2=adaptive'),
	'zoomspeed' => array(SET_TRANSFORM, 'default', 'Set percent of max zoom per frame if adaptive zoom enabled. Range is from 0 to 5, default is 0.25.'),
	'smoothing' => array(SET_TRANSFORM, 30, 'Set the number of frames (value*2 + 1), used for lowpass filtering the camera movements.'),

	'vcodec' => array(SET_FFMPEG, 'libx264', 'Video codec'),
	'preset' => array(SET_FFMPEG, 'slow', 'Encoding options preset'),
	'tune' => array(SET_FFMPEG, 'film', 'Fine tune settings to various inputs'),
	'crf' => array(SET_FFMPEG, 18, 'Quality factor (0-51), 0 is the best, 17 is visually lossless'),
	'acodec' => array(SET_FFMPEG, 'copy', 'audio codec'),
	'unsharp' => array(SET_FFMPEG, '5:5:0.8:3:3:0.4', 'unsharp filter parameters, no for disable unsharp'),
	'filter' => array(SET_USER, '', 'Additional filter with options (add multiple filter lines for more filters)'),
	'other' => array(SET_USER, '', 'Any additional ffmpeg or codec options (will be added as -option value)'),
	'filters' => array(SET_HIDDEN, ''),
	
);
$setpars = array(
	'Script settings',
	'Detect settings',
	'Transform settings',
	'ffmpeg settings',
	'User defined options'
);

$booleans = array('true'=>true, 'false'=>false, 'yes'=>true, 'no'=>false);

// Load default global settings from script directory
$default_set = dirname(__FILE__).'/ffstb.set';
if(file_exists($default_set)) loadSettings($default_set);

$filenames = array();

for($i=1; $i<$argc; $i++) {
	$p = $argv[$i];
	if($e=strpos($p, '=')) {
		$o = substr($p,0,$e);
		$v = substr($p,$e+1);
		if(substr($o,0,1)=='-') $o=substr($o,1);
		foreach($settings as $k=>$vv) {
			if(substr($k,0,strlen($o))==$o) {
				$settings[$k][1] = $v;
				break;
			}
		}
	}
	else if(substr($p,0,1)=='-') {
		$o = substr($p,1);
		foreach($settings as $k=>$v) {
			if(substr($k,0,strlen($o))==$o) {
				if(is_bool($v[1])) {
					$settings[$k][1] = true;
				}
				else {
					$i++;
					if(!isset($argv[$i])) { echo "Option $o needs an argument.\n"; exit; }
					else $settings[$k][1] = $argv[$i];
				}
				break;
			}
		}
	}
	else {
		$filenames[] = $p;
	}
}

// Load settings from data directory
if(file_exists($setfile = 'ffstb.set')) {
	loadSettings($setfile);
}

// Load settings from given file
if($settings['setfile'][1]) {
	$setfile = $settings['setfile'][1];
	if(!file_exists($setfile)) $setfile = dirname(__FILE__).'/'.$setfile;
	if(!file_exists($setfile)) $setfile = '';
	if($setfile) loadSettings($setfile);
}

// Preprocess options
unset($settings['filter']);
unset($settings['other']);
$options = $settings;
array_walk($options, function(&$v, $k) {$v = $v[1];});

$verbose = $options['verbose'];
$help = $options['help'];
if($verbose || $help) echo $title;
// Display help
if($help) echo $helptext;

// Display settings
if($verbose>2 || $help) {
	echo $settingsheader;
	foreach($setpars as $sk => $sp) {
		echo "\n### $sp\n\n";
		foreach($settings as $s => $set) {
			if($set[0]==$sk) {
				$sv = $set[1];
				$sh = $set[2];
				if(is_bool($sv)) $sv = ($sv?'yes':'no');
				echo "  $s=$sv\t# $sh\n";
			}
		}
	}
}

// Collecting files by input pattern
if(isset($options['pattern'])) {
	$pattern = $options['pattern'];
	if($pattern) {
		$valid = (@preg_match($pattern, null) !== false);
		if($valid) {
			$filenames = array_merge($filenames, array_filter(scandir('.'), function($item) use($pattern) { return preg_match($pattern, $item); }));
			if($verbose>=2) printf("\nFiles: %s\n\n", implode(', ', $filenames));
		}
		else echo "\nInvalid pattern!\n\n";
	}
}

const STATUS_WAIT=0;	// file is waiting to be processed
const STATUS_PROG=1;	// in progress
const STATUS_READY=2;	// file already processed
const STATUS_FAIL=3;	// process failed
const STATUS_SKIP=4;	// file skipped (processed before this session)

// Collects all filenames to process
$jobs = array(); // array of [path, status, size, time] where status is STATUS_XXX
$skip = 0;
foreach($filenames as $filename) {
	addFileOrDir($filename);
}
if($verbose==1 && $skip) echo "Skipping $skip existing file(s)\n";

// Process all files in the $jobs
$starttime = new DateTime();
foreach($jobs as &$job) {
	if($verbose>1) showStat();
	$t1 = time();
	$job[1] = STATUS_PROG;
	$success = stabFile($job[0]);
	$t2 = time();
	$job[1] = $success ? STATUS_READY : STATUS_FAIL;
	$job[3] = $t2-$t1;
}
if($verbose) showStat();
exit;

#--------------------------------------------------
function addFileOrDir($filename) {
	global $options, $verbose;
	$exts = explode(',', $options['exts']);
	if(file_exists($filename)) {
		if(is_dir($filename)) {
			if($verbose>1) echo "`$filename` is a directory\n";
			if ($dh = opendir($filename)) {
				while (($file = readdir($dh)) !== false) {
					$dot = strrpos($file, '.');
					$ext = $dot===false ? '' : strtolower(substr($file, $dot+1));
					if(strpos($file, '.stb.')) continue;
					if(in_array($ext, $exts)) {
						if(file_exists($filename.'/'.$file)) {
							addFile($filename.'/'.$file);
						}
					}
				}
				closedir($dh);
			}
		}
		else {
			addFile($filename);
		}
	}
	else {
		echo "File `$filename` not found.\n";
	}
}

function addFile($filename) {
	global $options, $jobs, $verbose, $skip;
	$trf = $options['trf'];
	$outx = $options['xout'];
	$outfile = $filename.'.stb.'.$outx;
	$tempfile = $filename.'.'.$trf;
	if(file_exists($outfile) && !$options['overwrite']) {
		if($verbose) echo "Output exists, skipping `$filename`\n";
		$skip++;
		return false;
	}
	else {
		if(file_exists($outfile)) unlink($outfile);
		if(file_exists($tempfile)) unlink($tempfile);
		$jobs[] = array($filename, STATUS_WAIT, filesize($filename), -1);
		return true;
	}
}

/**
 * Stabilizes an existing file.
 * @param string $filename
 * @return boolean -- success
 */
function stabFile($filename) {
	global $options, $verbose;
	$trf = $options['trf'];
	$outx = $options['xout'];
	$outfile = $filename.'.stb.'.$outx;
	$tempfile = $filename.'.'.$trf;

	if(file_exists($outfile)) {
		if($verbose) echo "Overwriting `$outfile`\n";
		unlink($outfile);
	}
	if(file_exists($tempfile)) unlink($tempfile);
	if($verbose) echo "Processing `$filename`\n";
	$ffmpeg = $options['ffmpeg'];
	$filters = $options['filters'] ? ','.$options['filters'] : '';
	if($options['unsharp']) $filters = ',unsharp='.$options['unsharp'];
	$optzoom = $options['optzoom']!='default' ? 'optzoom='.$options['optzoom'].':' : '';
	$zoomspeed = $options['zoomspeed']!='default' ? 'zoomspeed='.$options['zoomspeed'].':' : '';
	$quiet = '';
	if($verbose==0) $quiet = ' -loglevel fatal ';
	if($verbose==1) $quiet = ' -loglevel error ';
	if($verbose==2) $quiet = ' -loglevel warning ';
	if($verbose==3) $quiet = ' -hide_banner ';
	
	$detectcommand = "$ffmpeg $quiet -i $filename -vf vidstabdetect=stepsize=${options['stepsize']}:shakiness=${options['shakiness']}:accuracy=${options['accuracy']}:result=$tempfile -f null -";
	$transfcommand = "$ffmpeg $quiet -i $filename -vf vidstabtransform=input=$tempfile:zoom=${options['zoom']}:{$optzoom}{$zoomspeed}smoothing=${options['smoothing']}$filters -vcodec ${options['vcodec']} -preset ${options['preset']} -tune ${options['tune']} -crf ${options['crf']} -acodec ${options['acodec']} -x264-params keyint=48:no-scenecut $outfile";
	$output = array();
	if($verbose>2) echo $detectcommand."\n";
	echo exec($detectcommand, $output), "\n";
	if(file_exists($tempfile)) {
		$output = array();
		if($verbose>2) echo $transfcommand."\n";
		echo exec($transfcommand, $output), "\n";
		if(file_exists($tempfile) && !$options['keep']) unlink($tempfile);
		if(!file_exists($outfile) || filesize($outfile)==0) {
			echo "Failed transforming `$filename`\n";
			return false;
		}
	}
	else {
		echo "Failed detecting `$filename`\n";
		return false;
	}
	return true;
}

function execInBackground($cmd) { 
    if (substr(php_uname(), 0, 7) == "Windows"){ 
        pclose(popen("start /B ". $cmd, "r"));  
    } 
    else { 
        exec($cmd . " > /dev/null &");   
    }
} 

/**
 *	Loads settings from .set file to $settings array
 *	Unknown setting create `SET_USER` type option.
 *	`filter` setting will be added to `filters` option.
 */
function loadSettings($filename) {
	global $settings, $booleans;
	#echo "*** Loading $filename\n";
	$sf = file($filename);
	foreach($sf as $l) {
		$l = trim($l);
		$comm = '';
		if(substr($l,0,1)=='#') continue;
		if(($p = strpos($l, ' #')) || ($p = strpos($l, "\t#"))) {
			$comm = trim(substr($l, $p+2));
			$l = trim(substr($l, 0, $p));
		}
		if($p = strpos($l, '=')) {
			$var = trim(substr($l, 0, $p));
			$val = trim(substr($l, $p+1));
			if(isset($booleans[$val])) $val = $booleans[$val];
		}
		else {
			$var = $l;
			$val = false;
		}
		$opt = null;
		if($var=='') continue;

		if($var=='filter') {
			$opt = 'filters';
			$settings[$opt][1] .= ','.$val;
		}
		else foreach($settings as $k=>$v) {
			if($k==$var) {
				$opt = $k;
				$settings[$k][1] = $val;
				break;
			}
		}
		// If not found, creates new entry
		if(!$opt) {
			$settings[$var] = array(SET_USER, $val, $comm);
		}
	}
}

/**
 * @throws Exception
 */
function showStat() {
	// array of [path, status, size, time]
	global $jobs, $starttime;
	$files = 0;
	$ready = 0;
	$prog = 0;
	$fail = 0;
	$now = new DateTime();
	$elapsed = $now->diff($starttime);
	$size_all = 0;
	$size_ready = 0;
	$time_ready = 0;
	foreach($jobs as $job) {
		$files++;
		if($job[1]==STATUS_READY) { $ready++; $size_ready += $job[2]; $time_ready += $job[3]; }
		if($job[1]==STATUS_PROG) $prog++;
		if($job[1]==STATUS_FAIL) { $fail++; $ready++; $size_ready += $job[2]; $time_ready += $job[3]; }
		$size_all += $job[2];
	}
	$speed = $time_ready ? floor($size_ready / $time_ready) : 0; // byte/s
	$estimated = $speed ? floor($size_all / $speed) : 0; // estimated full time in s
	$finish = clone $now;
	$finish->add(new DateInterval('PT'.$estimated.'S'));
	$estint = $finish->diff($starttime);
	
	echo "---------------------------------------------------------------------\n";
	if($files) echo sprintf("$ready of $files are completed (%.1f %%).\n", $ready / $files * 100);
	else echo "No files specified or found\n";
	if($estimated) echo sprintf("Elapsed %s of expected %s (%.1f %%).\n", $elapsed->format('%ad %H:%I:%S'), $estint->format('%ad %H:%I:%S'), ($now->getTimestamp() - $starttime->getTimeStamp()) / $estimated * 100);
	echo sprintf("%s of %s processed at speed %s/s\n", sizeformat($size_ready), sizeformat($size_all), sizeformat($speed));
	if($fail) echo "$fail files failed\n";
}

function sizeformat($bytes, $decimals = 1) {
  $sz = 'BKMGTP';
  $factor = (int)floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
