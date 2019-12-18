<html>
<title>Guerilla Dungeons</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.23/moment-timezone-with-data.min.js" type="text/javascript"></script>
<script src="./guerrilla.js" type="text/javascript"></script>
<style>
.highlight{
	background-color:powderblue;
}
.highlight .highlight{
	font-weight: bold;
}
.group-tag{
	display: block;
	text-align: center;
	line-height: 1em;
}
.dungeon-icon{
	width: 50px;
	height: 50px;
}
.float{
	float: left;
}
</style>
</head>
<body>
<?php
include '../miru_common.php';
include './guerrilla.php';
echo get_guerrilla_schedule();
?>
</body>
</html>
