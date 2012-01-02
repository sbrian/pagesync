<?PHP

function removeDeleted($element)
{
	return $element != $_GET["delete"];
}

function makeHTML($data)
{
	$html = <<<EOT
<html>
<head>
<script type="text/javascript" src="../../javascript/jquery-1.6.js"></script>
<script type="text/javascript" src="../../javascript/ajaxslt-0.8.1/util.js"></script>
<script type="text/javascript" src="../../javascript/ajaxslt-0.8.1/xmltoken.js"></script>
<script type="text/javascript" src="../../javascript/ajaxslt-0.8.1/dom.js"></script>
<script type="text/javascript" src="../../javascript/ajaxslt-0.8.1/xpath.js"></script>
<script type="text/javascript" src="../../javascript/PageSync.js"></script>
<script type="text/javascript">

var pageSync = new PageSync();

</script>
</head>
<body>
Click an item to delete it:
<ol>
EOT;

	foreach($data as $element)
	{
		$html .= '<li><a href="?delete='.urlencode($element).'" onClick="pageSync.doServerAction(this.href+\'&amp;format=json\');return false;">'
			.htmlspecialchars($element).'</a></li>
	';
	}

	$html .= <<<EOT
</ol>
<form name="addForm" action="" method="get" onSubmit="pageSync.doServerAction(this.action+'?format=json&amp;add='+escape(this.add.value));return false;">
<input type="text" name="add"/>
<input value="Add" type="submit"/>
<br/>
<a href="?reset=on" onClick="pageSync.doServerAction(this.href+'&amp;format=json');return false;">Reset</a>
</form>
</body>
</html>
EOT;
	return $html;
}

ini_set("include_path", ini_get("include_path") .
	PATH_SEPARATOR . "../../php");

require_once("PageSync.php");

session_start();

$originalData = array(
	"Bob",
	"Joe",
	"Bill",
	"Larry",
	"Jeff"
);

$data = $_SESSION["data"];

if ( ! $data )
{
	if ( $_GET["format"] == "json" )
	{
		echo PageSync::doNoOriginal();
		return;
	}
	$data = $originalData;
}

if ( $_GET["delete"] )
{
	$oldData = $data;
	$data = array_filter($oldData, "removeDeleted");
}

if ( $_GET["add"] )
{
	$oldData = $data;
	$data[] = $_GET["add"];
}

if ( $_GET["reset"] )
{
	$oldData = $data;
	$data = $originalData;
}

$_SESSION["data"] = $data;

session_write_close();

if ( $_GET["format"] == "json" )
{	
	$json = PageSync::doProcessing(makeHTML($oldData), makeHTML($data));
	echo $json;
	return;
}

echo makeHTML($data);


