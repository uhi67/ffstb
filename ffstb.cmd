:: single file stabilizer command for Windows
:: Using: ffstb.cmd filename
ffmpeg -i %1 -vf vidstabdetect=stepsize=6:shakiness=8:accuracy=9:result=%1.trf -f null -
ffmpeg -i %1 -vf vidstabtransform=input="%1.trf":zoom=1:smoothing=30,unsharp=5:5:0.8:3:3:0.4 -vcodec libx264 -preset slow -tune film -crf 18 -acodec copy "%1.stb.mp4"
del %1.trf
