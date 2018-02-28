@echo off
:: Reverse a video
ffmpeg -hide_banner -i %1 -vf reverse %1.rev.mp4

