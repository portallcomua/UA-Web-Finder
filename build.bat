@echo off
setlocal
chcp 65001 >nul
title UA Web Finder - Build

echo ========================================
echo       UA Web Finder - EXE Builder
echo ========================================
echo.

where node >nul 2>nul
if errorlevel 1 (
  echo ERROR: Node.js was not found in PATH.
  echo Install Node.js from https://nodejs.org/ and restart this window.
  pause
  exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
  echo ERROR: npm was not found in PATH.
  pause
  exit /b 1
)

echo Node.js version:
node -v
echo npm version:
call npm -v
echo.

echo Installing project dependencies...
call npm install
if errorlevel 1 (
  echo.
  echo ERROR: npm install failed. Copy the error message above.
  pause
  exit /b 1
)

echo.
echo Building the Windows portable EXE...
call npm run dist
if errorlevel 1 (
  echo.
  echo ERROR: EXE build failed. Copy the error message above.
  pause
  exit /b 1
)

echo.
echo Build completed. Check the dist folder.
pause
endlocal
