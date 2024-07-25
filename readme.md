ffstb
=====
ffmpeg batch stabilizer script
------------------------------

A wrapper script for ffmpeg and vidstab plugin.

version 1.2

Prerequisites
-------------
- php >= 8
- ffmpeg as described below

Install
-------

### 1. Install ffmpeg with vidstab library. 
See [http://ffmpeg.org/](http://ffmpeg.org/)

- Windows:

Windows binary already contains *vidstab*.

- In Ubuntu Trusty 16.04:

```
sudo add-apt-repository ppa:mc3man/ffmpeg-test
sudo apt-get update
sudo apt-get install ffmpeg-static
hash -r
```

Now ffmpeg2 is the new command (note the "2").

More details [see at Doug McMahon](https://launchpad.net/~mc3man/+archive/ubuntu/ffmpeg-test)

### 2. Place php script to your script directory, and set path environment variable if needed.
### 3. Customize ffstb.set if needed 
In ubuntu, change `ffmpeg` value to `ffmpeg2`

See also [https://github.com/georgmartius/vid.stab](https://github.com/georgmartius/vid.stab).

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

filenames may be multiple filenames or directories to stabilize all matched files within.

### Options

#### -h	
	display help
#### -v num	
	verbose mode, 0=quiet, 1=basic, 2=detailed, 3=with ffmpeg progress
#### -o
	overwrite existing results
#### -r
	recurse subdirectories (if an input directory is given)
#### -k
	keep temporary files
#### -x ext
	output file extension (default is mp4)
#### -s filename
	use stabilize settings from this file (default ./ffstb.set is used)
#### -f path
	name of the ffmpeg command. Default is ffmpeg
#### -t n
	Maximum number of paralel threads (if multiple files are processed)

The script will process all given files and/or matched files (see exts option) in given directories.
The output files will be created in the same directory with name extended with '.stb' and output extension.
	
Settings in .set file
---------------------
Global default settings may be set in file `ffstb.set` in the directory of the script.
Settings for an input directory may be overridden with an `ffstb.set` file in it.
Lines or line endings beginning with # are comments.
The available settings with absolute defaults are:

### script settings

	overwrite=no	# Overwrite existing output. May be overridden with -o in command line
	recurse=no	    # Recurse subdirectories. May be overridden with -r in command line
	keep=no		    # Keep temporary files. May be overridden with -k in command line
	outx=mp4	    # Output file extension. May be overridden with -x in command line
	ffmpeg=ffmpeg	# name of ffmpeg command. May be overridden with -x in command line
			        # In ubuntu systems it may have to be 'ffmpeg2'
	exts=avi,m2t,mov,mpg,mpeg,mp2,mp4,mts
	threads=3	    # Maximum number of paralel threads

### detect settings
	
	stepsize=6	    # Set stepsize of the search process. 
	shakiness=8	    # Set the shakiness of input video or quickness of camera. (1-10)
	accuracy=9	    # Set the accuracy of the detection process. It must be a value in the range 1-15. 1 is the lowest.
	
### transform settings

	zoom=1		    # Set percentage to zoom. A positive value will result in a zoom-in effect. 0=noo zoom.
	optzoom=1	    # Set optimal zooming to avoid blank-borders. 0:disabled, 1=optimal, 2=adaptive
	zoomspeed=0.25	# Set percent of max zoom per frame if adaptive zoom enabled. Range is from 0 to 5, default is 0.25.
	smoothing=30	# Set the number of frames (value*2 + 1), used for lowpass filtering the camera movements. 
	
### ffmpeg and codec settings

	vcodec=libx264 
	preset=slow 	# Encoding options preset
	tune=film	    # Fine tune settings to various codec options (mainly the deblocking filter)
	crf=17 		    # Quality factor (0-51), 0 is the best, 17 is visually lossless
	acodec=mp3
    ab              # Audio bitrate
	unsharp=5:5:0.8:3:3:0.4		# unsharp filter luma_x:luma_y:luma_sh:chroma_x:chroma_y:chroma_sharp
	filter		    # Multiple filter options are appended to -vf option of pass 2

### User settings

Any other option=value pair will be applied to pass 2 as `-option value`

License
-------
Copyright (c) 2017 Uherkovich Péter
Lecensed under GNU General Public License v3.0

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, version 3 or any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program. If not, see http://www.gnu.org/licenses/

References
----------

- [http://ffmpeg.org/](http://ffmpeg.org/)
- [More details see at Doug McMahon](https://launchpad.net/~mc3man/+archive/ubuntu/ffmpeg-test)
- [https://github.com/georgmartius/vid.stab](https://github.com/georgmartius/vid.stab).
