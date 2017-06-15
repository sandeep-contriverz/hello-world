<script src="https://code.jquery.com/jquery-3.1.0.js" integrity="sha256-slogkvB1K3VOkzAI8QITxV3VzpOnkeNVsKvtkYLMjfk=" crossorigin="anonymous"></script>
<body>

<div id="div1"><div style="margin-top:200px;text-align: center;font-family: verdana;"><p>While the sync is processing, please don't hit the browser back button or cancel the page.</p><img src="45.gif" /></div></div>
</body>
<script>
$(document).ready(function(){
	$("#div1").load("sync-asq-script.php?data=1<?php if(isset($_GET['date']) && !empty($_GET['date'])){
		echo "&date=".$_GET['date'];
	}?><?php if(isset($_GET['token']) && !empty($_GET['token'])){
		echo "&token=".$_GET['token'];
	}?><?php if(isset($_GET['region']) && !empty($_GET['region'])){
		echo "&region=".urlencode($_GET['region']);
	}?>");
});
</script>