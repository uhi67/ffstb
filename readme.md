ffstb
=====
ffmpeg batch stabilizer script
------------------------------

version 0.1
(Not all described options are working)

Install
-------

1. Install ffmpeg with vidstab library. See [http://ffmpeg.org/](http://ffmpeg.org/)

    - Windows binary contains vidstab.
    - In Ubuntu Trusty 16.04
```
sudo add-apt-repository ppa:mc3man/ffmpeg-test
sudo apt-get update
sudo apt-get install ffmpeg-static
hash -r
```
    Now ffmpeg2 is the new command (note the "2").
    [More details see at Doug McMahon](https://launchpad.net/~mc3man/+archive/ubuntu/ffmpeg-test)

2. Place php script to your script directory, and set path environment variable if needed.
3. Customize ffstb.set if needed. In ubuntu, change `ffmpeg` value to `ffmpeg2`

Usage
-----
Windows:
```
php ffstb.php <filenames> <options>
```
Linux:
```
ffstb.php <filenames> <options>
```

- `<filename>`:	file or directory to stabilize
- `<option>`:
	* -h	display help
	* -o	overwrite existing results
	* -r	recurse subdirectories (if an input directory is given)
	* -k	keep temporary files
	* -x=ext	output file extension (default is mp4)
	* -s=filename	use stabilize settings from this file (default ./ffstb.set is used)
	* -f=path	name of the ffmpeg command. Default is ffmpeg

The script will process all given files or files in directories.
The output files will be created in the same directory with name extended with '.stb' and output extension.
	
Settings in .set file
---------------------
Global default settings may be set in ffstb.set in the directory of the script.
Settings for an input directory may be overridden with an ffstb.set file in it.
Lines or line endings beginning with # are comments.
The available settings with absolute defaults are:

### script settings

	overwrite=no	# Overwrite existing output. May be overridden with -o in command line
	recurse=no		# Recurse subdirectories. May be overridden with -r in command line
	keep=no			# Keep temporary files. May be overridden with -k in command line
	outx=mp4		# Output file extension. May be overridden with -x in command line
	ffmpeg=ffmpeg	# name of ffmpeg command. May be overridden with -x in command line
					# In ubuntu systems it may have to be 'ffmpeg2'
	exts=avi,m2t,mov,mpg,mpeg,mp2,mp4,mts

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
