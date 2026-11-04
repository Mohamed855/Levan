$ErrorActionPreference = 'Stop'
$paths = @('/', '/trial', '/privacy-policy', '/superadmin/login')
$results = foreach ($path in $paths) {
    try {
        $response = Invoke-WebRequest -Uri ('http://localhost:8000' + $path) -UseBasicParsing -TimeoutSec 10
        $visibleText = [System.Net.WebUtility]::HtmlDecode([regex]::Replace($response.Content, '<[^>]+>', ' '))
        [pscustomobject]@{
            path = $path
            status = [int]$response.StatusCode
            title = ([regex]::Match($response.Content, '<title>(.*?)</title>', 'Singleline')).Groups[1].Value.Trim()
            legacyBrandFound = $visibleText.Contains('مسند')
            etmamHeartFound = $response.Content.Contains('ti-heart-filled') -or $visibleText.Contains('❤️')
            centralLoginCopyFound = $visibleText.Contains('تسجيل الدخول للوحة تحكم الإدارة المركزية')
        }
    } catch {
        [pscustomobject]@{path = $path; error = $_.Exception.Message}
    }
}
$results | ConvertTo-Json -Depth 4
