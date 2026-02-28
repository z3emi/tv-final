# =======================================================
# =         سكربت إيقاف سيرفر البث بالكامل             =
# =======================================================

Write-Host "🛑 Stopping all streaming server processes..." -ForegroundColor Yellow

# قائمة بالعمليات التي سيتم إيقافها
$processesToStop = @(
    "nginx",
    "php-cgi",
    "python",
    "ffmpeg"
)

# إيقاف كل العمليات في القائمة
Stop-Process -Name $processesToStop -Force -ErrorAction SilentlyContinue

Write-Host "✅ All specified processes have been stopped." -ForegroundColor Green