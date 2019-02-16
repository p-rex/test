<html>
<head>
<style type="text/css">

/*** Title ***/
h2 {
//  background-color: #43779D;
  background-color: black;
  color: #fff;
  border-left: 0;
  padding: 15px 30px;
  margin: 40px -29px 20px;
  font-size: 26px;
  width: 1000px;
  margin: 0 auto;
}

/*** Goボタン ***/
#godiv {
    width: 1000px;
    margin: 0 auto;
    font-size: 13px;
}

/*** 結果のtextbox ***/
#resultdiv {
    width: 1050px;
    margin: 0 auto;
    font-size: 13px;
}


/*** チェック対象の文字列 ***/
.chk {
    color: red;
    font-weight: bold;
}


/*** table ***/
table.company {
    width: 1050px;
    margin: 0 auto;
    border-collapse: separate;
    border-spacing: 0px 15px;
    font-size: 12px;
}
 
table.company th,
table.company td {
    padding: 10px;
}
 
table.company th {
//    background: #295890;
    background: black;
    vertical-align: middle;
    text-align: left;
    width: 100px;
    overflow: visible;
    position: relative;
    color: #fff;
    font-weight: normal;
    font-size: 15px;
}
 
table.company th:after {
    left: 100%;
    top: 50%;
    border: solid transparent;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    border-color: rgba(136, 183, 213, 0);
//    border-left-color: #295890;
    border-left-color: black;
    border-width: 10px;
    margin-top: -10px;
}
/* firefox */
@-moz-document url-prefix() {
    table.company th::after {
        float: right;
        padding: 0;
        left: 30px;
        top: 10px;
        content: " ";
        height: 0;
        width: 0;
        position: relative;
        pointer-events: none;
        border: 10px solid transparent;
        border-left: #295890 10px solid;
        margin-top: -10px;
    }
}
 
table.company td {
    background: #f8f8f8;
    width: 200px;
    padding-left: 20px;
}
</style>



<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="jquery.blockUI.js"></script>
<script type="text/javascript">


$(function() {
  $('#chk').click(function() {
    $.blockUI({
      message: 'Please Wait...',
      css: {
        border: 'none',
        padding: '10px',
        backgroundColor: '#333',
        opacity: .5,
        color: '#fff'
      },
      overlayCSS: {
        backgroundColor: '#000',
        opacity: 0.6
      }
    });
    setTimeout($.unblockUI, 10000);
  });
}); 

$(function()
{
	$('#chk').click(
		function()
		{
			var url = document.getElementById('url').value;
			var req_hdr = document.getElementById('req_hdr').value;
//			var host_hdr = document.getElementsByName('host_hdr').value;
			var host_hdr = getRadioVal('host_hdr');
//			var proxy = document.getElementById('proxy').value;
			var post_str = document.getElementById('post_str').value;
			$.post(
				'kick.php',
				{
					'url': url,
					'req_hdr': req_hdr,
					'host_hdr': host_hdr,
//					'proxy': proxy,
					'post_str':post_str
				},
				function(data)
				{
					//alert(data);
					var result = document.getElementById('result_box');
					result.innerHTML = data;
					$.unblockUI();
				}
			);
		}
	);
});


function getRadioVal(name)
{
	var radioList = document.getElementsByName(name);
	for(var i=0; i<radioList.length; i++)
	{
		if (radioList[i].checked)
		{
			var str = radioList[i].value;
			break;
		}
	}
	return str;
}


</script>
<title>HTTP Request</title>
</head>
<body>

<h2>HTTP Request</h2>

<?php //var_dump(getallheaders()); ?>


<table class="company">
<tbody>
<tr><th>Target URL</th><td><input type="text" size=100 id="url" name="url"></td></tr>
<tr><th>Request Header</th><td><textarea id="req_hdr" name="req_hdr" cols="120" rows="15"><?php echo getReqHeaders(); ?></textarea></td></tr>
<tr><th>Host Header</th><td><input type="radio" id="host_hdr_auto" name="host_hdr" value="auto" checked>Target URLのFQDNを使用<input type="radio" id="host_hdr_org" name="host_hdr" value="org">上記Hostヘッダを使用</td></tr>
<!--
<tr><th>Proxy</th><td><input type="text" size=50 id="proxy" name="proxy" placeholder="Proxy:Port の形式で指定"></td></tr>
-->
<tr><th>Option</th><td><input type="text" size=100 id="post_str" name="post_str" placeholder="空欄の場合はGET、文字列指定したらPOST。HEADと書いたらHEAD。"></td></tr>

</tbody>
</table>

<br>

<div id="godiv"><input id="chk" name="chk" type="button" value="GO"></div>
<div id="resultdiv">

<br><br>


<div id="result_box" name="result_box"></div>
</div>



</body>
</html>



<?php

function getReqHeaders()
{
	$header = '';
	foreach (getallheaders() as $name => $value)
	{
		$header .= "$name: $value\n";
	}
//	var_dump('AAAAAAAA', $header);
	return $header;
	

}


?>
