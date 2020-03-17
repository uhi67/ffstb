@echo off
:: Reverse a video

@echo off
echo # Reverse a video #
if not "%1"=="" goto :g
	echo Example for reverse one file: ffrev filename
	echo Example for reverse a set of files: ffrev all *.mp4
	echo Output filename: input.rev.mp4
	goto :eof
	
:g
if "%1"=="all" goto :loop

set spd=%2
if "%spd%"=="" set spd=0.5
set output=%1
set output=%output:.mp4=%
ffmpeg -hide_banner -i %1 -vf reverse %output%.rev.mp4
goto :eof

:loop
for %%f in (%2) do call ffrev %%f

:: foreach ($file in get-ChildItem .\*.stb.mp4) { ffspd $file.name 0.25 }
:: CMD syntax: https://ss64.com/nt/syntax.html

:eof
