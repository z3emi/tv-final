# ==================================================================
# =      مدير متكامل + مراقبة + فحص IP وإعادة تشغيل تلقائي    =
# ==================================================================

# --- الإعدادات والمسارات ---
$nginxPath      = "C:\nginx\nginx.exe"
$nginxWorkDir   = "C:\nginx"
$phpPath        = "C:\php\php-cgi.exe"
$phpArgs        = "-b 127.0.0.1:9000 -c C:\php\php.ini"
$pythonLauncher = "C:\Program Files\PyManager\py.exe"
$pythonArgs     = "-3.13 C:\nginx\html\stream_with_latency.py"
$pythonWorkDir  = "C:\nginx\html"

# --- إعدادات فحص الـ IP ---
$TargetIPtoCheck = "45.156.223.102"

# --- إعدادات عامة ---
$MonitorInterval = 1    # افحص كل 5 ثوانٍ (يمكنك تغييرها إلى 1 إذا أردت)
$LogFile         = "C:\nginx\ps_manager.log"

# ------------------------------------------------------------------
# -------------------- بداية دوال السكربت --------------------
# ------------------------------------------------------------------

function Log($msg) {
    $stamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    "$stamp  $msg" | Out-File -FilePath $LogFile -Append -Encoding UTF8
}
try { New-Item -ItemType Directory -Force -Path "C:\nginx\logs" | Out-Null } catch {}

function Stop-JobSafe($name){
    $j = Get-Job -Name $name -ErrorAction SilentlyContinue
    if ($j) {
        try { Stop-Job -Job $j -ErrorAction SilentlyContinue } catch {}
        try { Wait-Job -Job $j -Any -Timeout 3 | Out-Null } catch {}
        try { Remove-Job -Job $j -ErrorAction SilentlyContinue } catch {}
    }
}

function Stop-Everything {
    Write-Host "🛑 Stopping all services and the background monitor job..." -ForegroundColor Yellow
    Log "ACTION: Stopping all services and background job."
    Stop-JobSafe -name "ServiceMonitorJob"
    Stop-Process -Name "nginx","php-cgi","python","python3","ffmpeg","py" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
}

function Start-Everything {
    Write-Host "🚀 Starting NGINX, PHP, and Python..." -ForegroundColor Green
    Log "ACTION: Starting all services."
    Start-Process -FilePath $nginxPath -WorkingDirectory $nginxWorkDir -WindowStyle Hidden
    Start-Process -FilePath $phpPath -ArgumentList $phpArgs -WindowStyle Hidden
    Start-Process -FilePath $pythonLauncher -ArgumentList $pythonArgs -WorkingDirectory $pythonWorkDir

    Write-Host "🛡️ Starting the integrated background monitor..." -ForegroundColor Cyan
    
    $MonitorScriptBlock = {
        param(
            $NginxPath, $NginxWorkDir, $PhpPath, $PhpArgs, 
            $PythonLauncher, $PythonArgs, $PythonWorkDir,
            $MonitorInterval, $LogFile, $TargetIP
        )

        function Log-Job($m){ $stamp=(Get-Date).ToString("yyyy-MM-dd HH:mm:ss"); "$stamp [JOB] $m" | Out-File -FilePath $LogFile -Append -Encoding UTF8 }
        
        $isIpDown = $false

        while($true){
            try{
                $connectionTest = Test-NetConnection -ComputerName $TargetIP -Port 80 -InformationLevel Quiet -WarningAction SilentlyContinue
                
                if ($connectionTest -and $isIpDown) {
                    Log-Job "SUCCESS: Connection to target IP $TargetIP restored. RELOADING EVERYTHING."
                    Stop-Process -Name "nginx","php-cgi","python","python3","ffmpeg","py" -Force -ErrorAction SilentlyContinue
                    Start-Sleep -Seconds 3
                    Start-Process -FilePath $NginxPath -WorkingDirectory $NginxWorkDir -WindowStyle Hidden
                    Start-Process -FilePath $PhpPath -ArgumentList $PhpArgs -WindowStyle Hidden
                    Start-Process -FilePath $PythonLauncher -ArgumentList $PythonArgs -WorkingDirectory $PythonWorkDir
                    $isIpDown = $false
                }
                elseif (-not $connectionTest -and -not $isIpDown) {
                    Log-Job "CRITICAL: Connection to target IP $TargetIP lost. Monitoring..."
                    $isIpDown = $true
                }

                if (-not (Get-Process -Name "nginx" -ErrorAction SilentlyContinue)) { Start-Process -FilePath $NginxPath -WorkingDirectory $NginxWorkDir -WindowStyle Hidden; Log-Job "NGINX was down. Restarted." }
                if (-not (Get-Process -Name "php-cgi" -ErrorAction SilentlyContinue)) { Start-Process -FilePath $PhpPath -ArgumentList $PhpArgs -WindowStyle Hidden; Log-Job "PHP-CGI was down. Restarted." }
                
            } catch { Log-Job "Job loop error: $($_.Exception.Message)" }
            Start-Sleep -Seconds $MonitorInterval
        }
    }

    Start-Job -Name "ServiceMonitorJob" -ScriptBlock $MonitorScriptBlock -ArgumentList @(
        $nginxPath, $nginxWorkDir, $phpPath, $phpArgs, 
        $pythonLauncher, $pythonArgs, $pythonWorkDir,
        $MonitorInterval, $LogFile, $TargetIPtoCheck
    )

    Start-Sleep -Seconds 2
    Log "Monitor job started."
}

# =======================================================
# =                   بداية التشغيل                      =
# =======================================================

Stop-Everything
Start-Everything

# --- حلقة الأوامر التفاعلية ---
while ($true) {
    Write-Host "==================================================================" -ForegroundColor Cyan
    Write-Host "✅ SERVER IS RUNNING. Monitor & IP Check are active." -ForegroundColor Green
    $input = Read-Host "Type 'restart' to reload, or 'stop' to exit"

    if ($input.ToLower() -eq 'stop') { break }

    if ($input.ToLower() -eq 'restart') {
        Write-Host "🔄 Reloading all services..." -ForegroundColor Yellow
        Log "ACTION: Manual reload requested."
        Stop-Everything
        Start-Everything
        Write-Host "✅ Services reloaded." -ForegroundColor Green
    }
}

Stop-Everything
Write-Host "✅ All processes and the monitor job have been stopped." -ForegroundColor Green
Log "Stopped everything."
Read-Host "Press Enter to close the window"