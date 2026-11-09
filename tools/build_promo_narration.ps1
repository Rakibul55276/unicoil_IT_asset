$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
$outputRoot = Join-Path $projectRoot "output\promotion"
$narrationPath = Join-Path $outputRoot "narration.txt"
$voicePath = Join-Path $outputRoot "narration.wav"

$voice = New-Object -ComObject SAPI.SpVoice
$availableVoices = $voice.GetVoices()
for ($index = 0; $index -lt $availableVoices.Count; $index++) {
    $candidate = $availableVoices.Item($index)
    if ($candidate.GetDescription() -like "*David*") {
        $voice.Voice = $candidate
        break
    }
}
$voice.Rate = 1
$voice.Volume = 100

$stream = New-Object -ComObject SAPI.SpFileStream
$format = New-Object -ComObject SAPI.SpAudioFormat
$format.Type = 22 # 44.1 kHz, 16-bit, mono PCM
$stream.Format = $format
$stream.Open($voicePath, 3, $false)
$voice.AudioOutputStream = $stream

$text = Get-Content -Raw -LiteralPath $narrationPath
[void] $voice.Speak($text)
$stream.Close()

Write-Output $voicePath
