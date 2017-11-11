#!/usr/bin/php
<?php
if($argc==1 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>
ffsb v0.1 - ffmpeg batch stabilizer script
==========================================
Usage
-----
<?php echo $argv[0]; ?> <filenames> <options>

<filename>	file or directory to stabilize
<option>
	-h			display help
	-o			overwrite existing results
	-r			recurse subdirectories (if an input directory is given)
	-k			keep temporary files
	-x <ext>	output file extension 
	-s <file>	use stabilize settings from this file (default ./ffstb.set is used)
	-f <path>	name of the ffmpeg command. Default is ffmpeg
	
Settings in .set file
---------------------
Global default settings may be set in ffstb.set in the directory of the script.
Settings for an input directory may be overridden with an ffstb.set file in it.
Lines or line endings beginning with # are comments.
The available settings with current global defaults are:

### script settings

	overwrite=no	# Overwrite existing output. May be overridden with -o in command line
	recurse=no		# Recurse subdirectories. May be overridden with -r in command line
	keep=no			# Keep temporary files. May be overridden with -k in command line
	xout=mp4		# Output file extension. May be overridden with -x in command line
	ffmpeg=ffmpeg	# name of ffmpeg command. May be overridden with -x in command line
					# In ubuntu systems it may have to be 'ffmpeg2'

### detect settings
	
	stepsize=6
	shakiness=8
	accuracy=9
	
### transform settings

	zoom=1
	smoothing=30
	unsharp=5:5:0.8:3:3:0.4	
	
### ffmpeg settings

	vcodec=libx264 
	preset=slow 
	tune=film 
	crf=18 
	acodec=copy

<?php
}
// Settings
$exts = array('avi', 'm2t', 'mov', 'mpg', 'mpeg', 'mp2','mp4', 'mts');
$default_set = 'ffstb.set';
$trf = 'trf';
$options = array(
	'overwrite' => false,
	'keep' => false,
	'recurse' => false,
	'xout' => 'mp4',
	'setfile' => null,
	'ffmpeg' => 'ffmpeg',
);

$filenames = array();
for($i=1; $i<$argc; $i++) {
	$p = $argv[$i];
	if(substr($p,0,1)=='-') {
		
	}
	else {
		$filenames[] = $p;
	}
}
foreach($filenames as $filename) {
	stabFileOrDir($filename);
}

function stabFileOrDir($filename) {
	global $exts;
	if(file_exists($filename)) {
		if(is_dir($filename)) {
			echo "`$filename` is a directory\n";
			if ($dh = opendir($filename)) {
				while (($file = readdir($dh)) !== false) {
					$dot = strrpos($file, '.');
					$ext = $dot===false ? '' : strtolower(substr($file, $dot+1));
					if(in_array($ext, $exts)) {
						if(file_exists($filename.'/'.$file)) {
							stabFile($filename.'/'.$file);
						}
					}
				}
				closedir($dh);
			}
		}
		else {
			stabFile($filename);
		}
	}
	else {
		echo "File `$filename` not found.\n";
	}
}

/**
 * Stabilizes an existing file.
 * Sikps existing results, unless -o given
 * @param string $filename
 * @return void
 */
function stabFile($filename) {
	global $options, $trf;
	$outx = $options['x'];
	$outfile = $filename.'.'.$outx;
	$tempfile = $filename.'.'.$trf;
	if(file_exists($outfile) && !$options['o']) {
		echo "Output exists, skipping `$filename`\n";
	}
	else {
		if(file_exists($outfile)) unlink($outfile);
		if(file_exists($tempfile)) unlink($tempfile);
		echo "Processing `$filename`\n";
		$ffmpeg = $options['f'];
		$detectcommand = "$ffmpeg -i $filename -vf vidstabdetect=stepsize=6:shakiness=8:accuracy=9:result=$tempfile -f null -";
		$transfcommand = "$ffmpeg -i $filename -vf vidstabtransform=input=$tempfile:zoom=1:smoothing=30,unsharp=5:5:0.8:3:3:0.4 -vcodec libx264 -preset slow -tune film -crf 18 -acodec copy $outfile";
		echo exec($detectcommand), "\n";
		if(file_exists($tempfile)) {
			echo exec($transfcommand), "\n";
			if(file_exists($tempfile) && !$options['k']) unlink($tempfile);
			if(!file_exists($outfile)) {
				echo "Failed transforming `$filename`\n";
			}
		}
		else {
			echo "Failed detecting `$filename`\n";
		}
	}
}

function execInBackground($cmd) { 
    if (substr(php_uname(), 0, 7) == "Windows"){ 
        pclose(popen("start /B ". $cmd, "r"));  
    } 
    else { 
        exec($cmd . " > /dev/null &");   
    } 
} 
