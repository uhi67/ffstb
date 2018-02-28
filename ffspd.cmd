@echo off
:: Speed up a video
set spd=%2
if "%spd%"=="" set spd=0.5
set output=%1
set output=%output:.mp4=%
ffmpeg -hide_banner -i %1 -filter:v "setpts=%spd%*PTS" -an %output%.spd.mp4
::ffmpeg -hide_banner -i %output%.spd.mp4 -filter "minterpolate='mi_mode=mci:mc_mode=aobmc:vsbmc=1:fps=120'" -vcodec libx264 -preset slow -tune fastdecode -crf 15 -an -x264-params keyint=48:no-scenecut %output%.spd.sm.mp4
