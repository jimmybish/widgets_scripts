<#
.SYNOPSIS

Performs all AD-related tasks when commissioning a new server to replace an old one.

.DESCRIPTION

IMPORTANT: Edit the script with Domain, OU and AD group names before running!

This script can:
- Create the new server's local admin group in Active Directory (Sec_%COMPUTERNAME%_Admin, which gets assigned by GP)
- Migrate admins from both the source server's local admins and source server's Sec_%COMPUTERNAME%_Admin group
- Perform the same steps for RDP users
- Add the new server to any AD groups the old server is a member of (WSUS, etc)
- Set the description of the new server object in AD
- Moves the new server to the same OU as the old
- Sets descriptions for all AD objects

.PARAMETER Source

The source server to copy users and settings from.

.PARAMETER Destination

The target server for users and settings to be added.

.PARAMETER ChangeNum

The Change ticket reference from your ticketing system (Used for updating descriptions of created AD objects)

.EXAMPLE

.\Migrate-Server.ps1 -Source OLD_SERVERNAME -Destination NEW_SERVERNAME - ChangeNum CH0000123

.EXAMPLE

.\Migrate-Server.ps1 OLD_SERVERNAME NEW_SERVERNAME CH0000123

#>


<#
ChangeLog
---------
v1.0    -    30/10/2019    -    Bishopj    -    Initial version
v1.1    -    12/11/2019    -    Bishopj    -    Added prompt to move the server to the correct OU
v1.2    -    20/11/2019    -    Bishopj    -    - Renamed to Migrate-Server 
                                                - Added prompt to add new server to same AD groups as old
                                                - Added RDP users to migrate
                                                - Added ability to update server description in AD
#>


Param (
    [Parameter(Mandatory=$true)]
    [String]$Source,
    [Parameter(Mandatory=$true)]
    [String]$Destination,
    [Parameter(Mandatory=$true)]
    [String]$ChangeNum
)

# Define the source and destination groups in AD
$Domain = "DOMAIN*" # The asterix is a wildcard for queries
$GroupsOU = "OU=Groups,DC=domain,DC=local"
$SourceAdminGroup = "Sec_" + $Source + "_Admin"
$SourceRDPGroup = "Sec_" + $Source + "_RDP"
$DestAdminGroup = "Sec_" + $Destination.ToUpper() + "_Admin"
$DestRDPGroup = "Sec_" + $Destination.ToUpper() + "_RDP"


Function Migrate-Admins ($Destination, $DestAdminGroup, $DomainAdminMembers, $ChangeNum)
{
    # Create the group (if needed) and add the users
    Try 
    {
        Get-ADGroup $DestAdminGroup
        Write-Host "$DestAdminGroup exists. Continuing."
    }
    catch
    {
        Write-Host "Creating $DestAdminGroup"
        Try
        {
            New-ADGroup -Name $DestAdminGroup -SamAccountName $DestAdminGroup -GroupCategory Security -GroupScope Global -Path $GroupsOU -Description "$ChangeNum :: Local Admins for $Destination"
            # If the script is too quick, it may report the group as not existing yet.
            # Cycle for 10 seconds before timing out.
            $i = 0
            Do
            {
                Write-Host "Looking for $DestAdminGroup in AD...."
                Try
                {
                    $GroupExists = Get-AdGroup $DestAdminGroup -ErrorAction SilentlyContinue
                }
                Catch
                {
                    Write-Host "Group not found yet" 
                }
                Start-Sleep -Seconds 1
                $i++
            }
            While (!$GroupExists -and $i -le 10)
            If ($i -eq 10) 
            { 
                Write-Warning "Timed out. Please check AD manually and try running again"
                Exit 1 
            }
    
        } Catch
        {
            Write-Warning "Group Creation Failed. Please create group manually and try running again"
            Write-Error $_.Exception.Message
            Exit 1
        }
    }
    Write-Host "$DestAdminGroup found in AD. Adding members"
    Add-ADGroupMember $DestAdminGroup -Members $DomainAdminMembers
}

Function Migrate-RDPUsers ($Destination, $DestRDPGroup, $DomainRDPUsers, $ChangeNum)
{
    # Create the group (if needed) and add the users
    Try 
    {
        Get-ADGroup $DestRDPGroup
        Write-Host "$DestRDPGroup exists. Continuing."
    }
    catch
    {
        Write-Host "Creating $DestRDPGroup"
        Try
        {
            New-ADGroup -Name $DestRDPGroup -SamAccountName $DestRDPGroup -GroupCategory Security -GroupScope Global -Path $GroupsOU -Description "$ChangeNum :: RDP Users for $Destination"
            # If the script is too quick, it may report the group as not existing yet.
            # Cycle for 10 seconds before timing out.
            $i = 0
            Do
            {
                Write-Host "Looking for $DestRDPGroup in AD...."
                Try
                {
                    $GroupExists = Get-AdGroup $DestRDPGroup -ErrorAction SilentlyContinue
                }
                Catch
                {
                    Write-Host "Group not found yet" 
                }
                Start-Sleep -Seconds 1
                $i++
            }
            While (!$GroupExists -and $i -le 10)
            If ($i -eq 10) 
            { 
                Write-Warning "Timed out. Please check AD manually and try running again"
                Exit 1 
            }
    
        } Catch
        {
            Write-Warning "Group Creation Failed. Please create group manually and try running again"
            Write-Error $_.Exception.Message
            Exit 1
        }
    }
    Write-Host "$DestRDPGroup found in AD. Adding members"
    Add-ADGroupMember $DestRDPGroup -Members $DomainRDPUsers
}


# Get members of the Local Administrators Group
$SourceAdmins = Invoke-Command { 
    $Members = net localgroup administrators | 
    where {$_ -AND $_ -notmatch "command completed successfully"} | 
    Select -skip 4 
    New-Object PSObject -Property @{ 
    Members=$Members 
    } 
} -Computername $Source -HideComputerName | 
Select * -ExcludeProperty RunspaceID

$SourceRDPs = Invoke-Command { 
    $Members = net localgroup "Remote Desktop Users" | 
    where {$_ -AND $_ -notmatch "command completed successfully"} | 
    Select -skip 4 
    New-Object PSObject -Property @{ 
    Members=$Members 
    } 
} -Computername $Source -HideComputerName | 
Select * -ExcludeProperty RunspaceID

# Filter out domain users/groups that aren't default GP assigned ones, then remove the domain from the string
[Array]$DomainAdminMembers = $SourceAdmins.Members | Where-Object { $_ -like $Domain -and $_ -notlike "*Domain Admins" -and $_ -notlike "*ServerAdmins" -and $_ -ne "HTGAdmin" -and $_ -notlike "*$SourceAdminGroup" } | % { $_.Split('\')[1] }

# If there's an AD Group of admins for the old server, grab those members, too
Try
{
    $DomainAdminMembers += (Get-ADGroupMember $SourceAdminGroup -ErrorAction SilentlyContinue | Select-Object SamAccountName).SamAccountName
}
Catch
{
    Write-Host "$SourceAdminGroup doesn't exist. Continuing."
}

Clear
Write-Host "Admins to migrate:"
Write-Host "------------------"
Write-Host $DomainAdminMembers
Write-Host ""
$ConfirmAdmin = Read-Host "Migrate admins to the new server? [ Y / N ]"
Switch ($ConfirmAdmin)
{   
    Y { Migrate-Admins -Destination $Destination -DestAdminGroup $DestAdminGroup -DomainAdminMembers $DomainAdminMembers -ChangeNum $ChangeNum }
    N { Continue }
}


# Now Migrate the RDP users. Same deal as above
[Array]$DomainRDPUsers = $SourceRDPs.Members | Where-Object { $_ -like $Domain -and $_ -notlike "*$SourceRDPGroup" } | % { $_.Split('\')[1] }
Try
{
    $DomainRDPUsers += (Get-ADGroupMember $SourceRDPGroup -ErrorAction SilentlyContinue | Select-Object SamAccountName).SamAccountName
}
Catch
{
    Write-Host "$SourceRDPGroup doesn't exist. Continuing."
}

Write-Host ""
Write-Host ""
Write-Host "RDP users to migrate:"
Write-Host "---------------------"
Write-Host $DomainRDPUsers
Write-Host ""
$ConfirmRDP = Read-Host "Migrate RDP users to the new server? [ Y / N ]"
Switch ($ConfirmRDP)
{   
    Y { Migrate-RDPUsers -Destination $Destination -DestRDPGroup $DestRDPGroup -DomainRDPUsers $DomainRDPUsers -ChangeNum $ChangeNum }
    N { Continue }
}



# Assign the AD group to local admins on the destination server - This should be done by GP, anyway
# TODO: Will add a check and only run code if necessary. For now, leave it out
#Invoke-Command -ComputerName $Destination -ScriptBlock { 
#    $DestAdminGroup = $using:DestAdminGroup
#    Add-LocalGroupMember -Group Administrators -Member "HYDROTASMANIA\$DestAdminGroup" } 


# Move the destination (new) Computer Object to the same OU as the source
$SourceDN = (Get-ADComputer $Source -Properties DistinguishedName | Select-Object DistinguishedName).DistinguishedName # The distinguished name for $Source
$DestinationDN = (Get-ADComputer $Destination -Properties DistinguishedName | Select-Object DistinguishedName).DistinguishedName # The distinguished name for $Destination
$TargetOU = $SourceDN -replace "CN=$Source,", ''
Write-Host ""
Write-Host ""
$MoveOUConfirm = Read-Host "Move $Destination to $TargetOU while you're here? [ Y / N ]"
Switch ($MoveOUConfirm) 
{
    Y { Move-ADObject -Identity $DestinationDN -TargetPath $TargetOU  }
    N { Continue }
}


# If the Source server is a member of any groups, add the destination to the same groups
$ServerGroups = Get-ADPrincipalGroupMembership $SourceDN
Write-Host ""
Write-Host ""
Write-Host "$Source is a member of the below groups:"
Write-Host "$($ServerGroups.Name)"
$AddGroupsConfirm = Read-Host "Add $Destination to these groups? [ Y / N ]"
Switch ($AddGroupsConfirm)
{
    Y { $ServerGroups | Add-ADGroupMember -Members $DestinationDN }
    N { Continue }
}

# Update Computer Description in AD
$DestinationDN = (Get-ADComputer $Destination -Properties DistinguishedName | Select-Object DistinguishedName).DistinguishedName # Refresh, in case there's a new location
Write-Host ""
Write-Host ""
$ADdescription = (Get-ADComputer $Destination -Properties Description).Description
If (!$ADdescription) { $ADdescription = 'empty' }
Write-Host "The current description for the computer object in AD is: $ADdescription"
Write-Host "Please enter a new desctription or press enter to leave it unmodified"
Write-Host "Note: The Change ticket reference will automatically be prepended to the description"
$Description = Read-Host "New Description - $ChangeNum :"
If ($Description)
{
    Try
    {
        Set-ADObject $DestinationDN -Description "$ChangeNum :: $Description"
        Write-Host "Description successfully updated"
    }
    Catch
    {
        Write-Error "Update Failed. Please try updating manually or run the script again" 
    }
}


Write-Host ""
Write-Host "All migration tasks complete!"
