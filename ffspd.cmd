@echo off
echo # Speed up a video #
if not "%1"=="" goto :g
	echo Example for doubling speed: ffspd filename 0.5 [ext]
	echo Example for quadrouple a set of files: ffspd all *.mp4 0.25 [ext]
	echo Output file name is "<original>.ext.mp4", where default of ext is "spd"
	goto :eof
	
:g
if "%1"=="all" goto :loop

set spd=%2
if "%spd%"=="" set spd=0.5
set output=%1
set output=%output:.mp4=%
set ext=%3
if "%ext%"=="" set ext="spd"

ffmpeg -hide_banner -i %1 -filter:v "setpts=%spd%*PTS" -an %output%.%ext%.mp4
echo -------------------------
echo %output%.%ext%.mp4 is ready
goto :eof

:loop
for %%f in (%2) do call ffspd %%f %3 %4

::ffmpeg -hide_banner -i %output%.spd.mp4 -filter "minterpolate='mi_mode=mci:mc_mode=aobmc:vsbmc=1:fps=120'" -vcodec libx264 -preset slow -tune fastdecode -crf 15 -an -x264-params keyint=48:no-scenecut %output%.spd.sm.mp4
:: foreach ($file in get-ChildItem .\*.stb.mp4) { ffspd $file.name 0.25 }
:: CMD syntax: https://ss64.com/nt/syntax.html

:eof
