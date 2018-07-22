# Jiraboard
Simple wallboard to connect to the Jira API and list tickets that haven't been updated since creation.
As this is made for our internal infrastructure, the config.ini.php file has been removed. This only held 'user', 'password' and 'url' values. The full URL in the ini included the JQL query in one string, but could be split for multiple queries easily enough.

The JQL query is /rest/api/latest/search?jql=project+=+"PROJECT"+AND+createdDate+>+"-1d"+ORDER+BY+createdDate+DESC

The front-end will either be green or red, based on whether there's a ticket to action or not. 

One downside is that if the end-user raises the ticket and then changes or updates anything themselves, that ticket will no longer be included. A workaround, depending on how the team uses Jira, is to change the JQL query to return unnasigned tickets, rather than compare dates.
