Function List-Processes ()
{
    Clear
    Write-Host "Current Running Processes"
    $Processes = Get-Process -IncludeUserName | Select ProcessName, Id, UserName | Where-Object { $_.ProcessName -notlike 'AmAgent*' -and $_.UserName -notlike '*SYSTEM' } | Sort-Object UserName, ProcessName

    $SelectedProcess = $Processes | Out-GridView -Title "Process Killer - Select Process" -PassThru

    Return $SelectedProcess
}

$Process = List-Processes

Add-Type -AssemblyName PresentationCore,PresentationFramework
$MessageboxTitle = “Process Killer”
Do 
{
    If ($Process)
    {
        Try 
        {
            Stop-Process -Id $Process.Id -Confirm
        }
        Catch
        {
            $ButtonType = [System.Windows.MessageBoxButton]::OK
            $MessageboxIcon = [System.Windows.MessageBoxImage]::Error
            $Messageboxbody = "There was a problem killing the process. Please save your work and restart your workstation"
            [System.Windows.MessageBox]::Show($Messageboxbody,$MessageboxTitle,$ButtonType,$MessageboxIcon)
        }
        $Process = $null
    }
    
    $ButtonType = [System.Windows.MessageBoxButton]::YesNo
    $MessageboxIcon = [System.Windows.MessageBoxImage]::Question
    $Messageboxbody = “Would you like to kill another process?”    
    $Continue = [System.Windows.MessageBox]::Show($Messageboxbody,$MessageboxTitle,$ButtonType,$MessageboxIcon)
    Switch ($Continue)
    {
        Yes { $Process = List-Processes }
        No { Exit 0 }
    }
}
While ($Continue -eq 'Y')
