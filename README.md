# widgets_scripts
Various little widgets, scripts and random things I've knocked up to make life easier

# /address_geocoder
PHP

I needed to gather the latlong coordinates for quite a lot of addresses in order to use them for heatmaps and so on. The plan was to present the data in an existing app created in Oracle APEX, and I had no budget to use any pro-level geocoding services. This script in the result!
It queries the Oracle DB for addresses, queries Google Maps (using as many free API keys as you can throw at it), and inserts the latlong data.

# /distance_calculator
Javascript, CSS

I was presented a problem where we need to compare the distance between a given address with 3 locations, and calculate which location is the closest in order to quote travel times to the address. 
The script stores the 3 locations in an array (more can be added) and loops through each one, getting the distance between each location and the address using the Haversine formula. Results are then stored in an array, ordered, and presented in a table.

# /jiraboard
Javascript, PHP, CSS

This is old and obsolete now. Jira has great boards built-in. But I wrote this back when I worked in a service desk and we needed something to display up on our wallboards. This one is a simple wallboard to connect to the Jira API and list tickets that haven't been updated since creation.

# /nagiosboard
Javascript, PHP, CSS

This was a wallboard I created when working in a Service Desk Role a few years back. It scrapes the status page of Nagios Core 3.2.3 and displays the current errors in large wallboard format. Suitable for 2 environments with separate status pages. In our case, "Office" and "Production".

# /statuspage
Javascript, PHP, CSS

A simple slideUp widget showing a quick overview of the component statuses from StatusPage. Ideal of an Intranet environment since it refreshes using a scheduled php task, not from every end-user's browser. 

# /migrate-server.ps1
PowerShell

This is a script to copy a number of settings from one server to another, useful for migrating to new operating systems or building multiple servers with the same configuration. This includes local admin accounts (using an Active Directory group), Remote Desktop users, the computer object's group membership, and the OU the computer object belongs in.
