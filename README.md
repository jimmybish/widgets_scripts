# widgets_scripts
Various little widgets, scripts and random things I've knocked up to make life easier

/distance_calculator
I was presented a problem where we need to compare the distance between a given address with 3 locations, and calculate which location is the closest in order to quote travel times to the address. 
The script stores the 3 locations in an array (more can be added) and loops through each one, getting the distance between each location and the address using the Haversine formula. Results are then stored in an array, ordered, and presented in a table.

/statuspage
A simple slideUp widget showing a quick overview of the component statuses from StatusPage. Ideal of an Intranet environment since it refreshes using a scheduled php task, not from every end-user's browser.
