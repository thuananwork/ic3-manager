@echo off
title CONG CU LAY IP TU DONG - THAY AN
color 0a
cls

echo ==========================================
echo        DANG KET NOI VOI MAY THAY...
echo ==========================================

:: Lay ten may tinh hien tai
set pcname=%COMPUTERNAME%

:: Tu dong tim Desktop
set DESKTOP_PATH=%USERPROFILE%\Desktop
if not exist "%DESKTOP_PATH%" set DESKTOP_PATH=%USERPROFILE%\OneDrive\Desktop

echo May hien tai: %pcname%
echo User: %USERNAME%

:: Gui IP ve may thay (IP: 10.217.7.119)
curl -s "http://10.217.7.119/ic3-manager/get_ip.php?pc=%pcname%"

echo.
echo ------------------------------------------
echo THANH CONG! IP cua ban da duoc gui ve.
echo ------------------------------------------
timeout /t 3
exit
