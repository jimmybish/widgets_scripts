<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Jira Wallboard</title>
    <link rel="stylesheet" type="text/css" href="./css/style.css"/>
    <script src="inc/jquery-2.1.3.min.js"></script>
    <script src="inc/ajax.js"></script>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            loadData.init();
        });
    </script>
</head>

<body>

<div id="head">New Tickets</div>
<p></p>
<div id="unhandled"></div>

<div id="list">
    <ul>

    </ul>
</div>

<div id="footer"></div>

</body>
</html>
