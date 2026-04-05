@echo off
title THU THAP IP MAY TINH
color 0b
echo Dang kiem tra so may...

:: Lay ten may tinh hien tai
set pcname=%COMPUTERNAME%

echo May cua ban la: %pcname%
echo Dang gui thong tin ve may thay...

:: Gui IP va Ten may ve web server cua thay
curl "http://192.168.50.135/ketqua/get_ip.php?pc=%pcname%"

echo.
echo ------------------------------------------
echo XONG! Thay da nhan duoc IP cua may ban.
echo ------------------------------------------
pause