<?php

$orientation = "LR";
$live_edit_link = "./";
$default = file_get_contents("default.csv");
$config = getRemoteJsonDetails ("config.json", false, true);
$examples = getRemoteJsonDetails ("examples.json", false, true);

if (isset($_POST["triplesTxt"]) and $_POST["triplesTxt"])
  {$triplesTxt = checkTriples ($_POST["triplesTxt"]);}
else if (isset($_GET["example"]) and isset($examples[$_GET["example"]]))
  {$ex = $examples[$_GET["example"]];
   $triplesTxt = checkTriples (file_get_contents($ex["uri"]));}
else if (isset($_GET["data"]))
  {$triplesTxt = gzuncompress(base64_decode($_GET["data"])); }
else
  {$triplesTxt = checkTriples ($default);}

$data = urlencode(base64_encode(gzcompress($triplesTxt)));
$bookmark = './?data='.$data;

$triples = explode("\n", $triplesTxt);
$raw = getRaw($triples);
$mermaid = Mermaid_formatData ($raw["test"]);

$html = buildPage ($triplesTxt, $mermaid);
echo $html;
exit;

////////////////////////////////////////////////////////////////////////

function buildExamplesDD ()
  {
  global $examples;

  ob_start();
  echo <<<END
  <div class="dropdown show" style="display: inline-block;margin-left: 8px;">
  <a class="btn btn-default dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Examples
  </a>
  <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
END;
  $html = ob_get_contents();
  ob_end_clean(); // Don't send output to client

  foreach ($examples as $k => $a)
    {$html .= "<a class=\"dropdown-item\" href=\"./?example=$k\">$a[title]</a>\n";}

  $html .= "</div></div>";

  return ($html);
  }


function debugJsonConversaion ($json, $php, $triples)
  {
  global $examples;
  $php = print_r($php, true);
  
  ob_start();
  echo <<<END
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container-fluid" style="padding:0px;">
  
  <div class="container-fluid" style="padding:0px;">
 
    <div class="row" style="padding:0px;margin:0px;">
      <div class="col-sm-4" style="padding:0px;height:98vh;background-color:white;">
	<pre style="height:100%;overflow:scroll;">$json</pre></div>
      <div class="col-sm-4" style="padding:0px;height:98vh;background-color:#efefef;">
	<pre style="height:100%;overflow:scroll;">$php</pre></div>
      <div class="col-sm-4" style="padding:0px;height:98vh;background-color:white;">
	<pre style="height:100%;overflow:scroll;">$triples</pre></div>
    </div>
    <br>
    
  </div>
</div>

</body>
</html>

END;
  $html = ob_get_contents();
  ob_end_clean(); // Don't send output to client

  echo $html;
  exit;
  }

  
function buildPage ($triplesTxt, $mermaid)
  {
  global  $live_edit_link, $bookmark;

  $exms = buildExamplesDD ();
  
  ob_start();
  echo <<<END

<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html> <!--<![endif]-->
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta charset="utf-8">
  <title></title>
  <link href="https://unpkg.com/bootstrap@4.5.0/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="local.css" rel="stylesheet" type="text/css">
  <style></style>
</head>
<body>
<div id="page">
  <div id="editor" class="textdiv">
    <form id="triplesFrom" action="./" method="post">
      <a title="The National Gallery" href="https://www.nationalgallery.org.uk/" target="_blank"  class="imbutton" style="margin-left: 8px; margin-top: 8px;" >
	<img src="graphics/ng-logo-black-100x40.png" width="32" /></a>

      <button class="btn btn-default nav-button" style="margin-bottom: 16px;" type="submit">Update</button>
      <button class="btn btn-default nav-button" style="margin-bottom: 16px;" id="clear" type="button">Clear</button>
      $exms

      <a title="GitHub Dynamic Modelling" href="https://github.com/jpadfield/dynamic-modelling" target="_blank"  class="imbutton" style="margin-right: 8px; float:right; margin-top: 8px;" >
	<img src="graphics/GitHub-Mark-64px.png" width="32" /></a>
	
      <a title="Mermaid Live Editor" href=" $live_edit_link" target="_blank" class="btn btn-default nav-button" style="margin-right: 16px; float:right; margin-bottom: 16px;" id="getIm" type="button">Get Image</a>      
      <a title="Bookmark of last updated graph" href="$bookmark" target="_blank" class="btn btn-default nav-button" style="margin-right: 8px; float:right; margin-bottom: 16px;" id="getIm" type="button">Bookmark</a>
      <button type="button" class="btn btn-default" style="margin-top: 8px; float:right; " data-toggle="modal" data-target="#helpModalCenter">Help</button>
      
      <div id="textholder" class="textareadiv form-group flex-grow-1 d-flex flex-column">
      <textarea class="form-control flex-grow-1 rounded-0 detectTab" id="triplesTxt" name="triplesTxt" rows="10">$triplesTxt</textarea>
      <button class="btn btn-default textbtn" id="tfs" type="button"  onclick="togglefullscreen('tfs', 'textholder')"><img src="graphics/view-fullscreen.png" width="20" /></button>
      </div>
    </form>
  </div>
      
<div id="holder" class="moddiv">
  <div id="split-container">
      <button class="btn btn-default nav-button" id="fs" style="padding: 5px; margin-right: 16px; float:right;" onclick="togglefullscreen('fs', 'holder')"><img src="graphics/view-fullscreen.png" width="20" /></button>
    <div id="graph-container" style="color:white;">
      <div id="graph">$mermaid</div>
    </div>
  </div> <!-- CLOSE split-container -->
</div> <!-- CLOSE holder -->      

</div>

<!-- Modal -->
<div class="modal fade" id="helpModalCenter" tabindex="-1" role="dialog" aria-labelledby="helpModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="helpModalLongTitle">Instructions</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
      
  <script src="https://unpkg.com/jquery@3.4.1/dist/jquery.min.js"></script>	<script src="https://unpkg.com/tether@1.4.7/dist/js/tether.min.js"></script>
  <script src="https://unpkg.com/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/mermaid@8.5.2/dist/mermaid.min.js"></script>
   <script src="local.js"></script>
  <script></script>  
  </body>
</html>

END;
$html = ob_get_contents();
ob_end_clean(); // Don't send output to client

return($html);
}

function getRaw($data)
  {
  global $orientation;

  $output = array();
	
  $no = 0;
  $bn = 0;
  $tn = 0;
  $ono = 0;
  $bnew = false;
  $bba = array();
  $bbano = 1;
 
    $tag = "test";
    $output[$tag]["model"] = $tag;
    $output[$tag]["comment"] = ucfirst ($tag)." Model";
    $output[$tag]["count"] = 0;

    foreach ($data as $k => $line) 
      {
      if (preg_match("/^.+\t.+\t.+$/", $line, $m))
	{$trip = explode ("\t", $line);}
      else if (preg_match("/^.+[,].+[,].+$/", $line, $m))
	{$trip = explode (",", $line);}
      else
	{$trip = array($line);}
      
      $trip = array_map('trim', $trip);

      // Increment triple number
      $tn++;
	
      if(preg_match("/^[\/][\/][ ]Model[:][\s]*([a-zA-Z0-9 ]+)[\s]*[\/][\/](.+)$/", $line, $m))
	{$output[$tag]["comment"] = $m[2];}
      else if(preg_match("/^[\/][\/][ ]*[gG]raph[ ]*([LT][BR])(.*)$/", $line, $m))
	{$orientation = $m[1];}
      // ignore lines that are commented out
      else if(preg_match("/^[\/#][\/#].*$/", $line, $m)) 
	{$trip = array($line);}

      // Ignore notes, empty lines or commented lines
      if (isset($trip[2]))
	{
	if (in_array($trip[0], array("_Blank Node", "_BN", "_bn")))
	  {$bnd = true;}
	else
	  {$bnd = false;}

	if (in_array ($trip[1], array("crm:P2.has_type", "crm:P2.has type", "has type", "type", "rdf:type")))
	  {$pt = true;}
	else
	  {$pt = false;}

	// Ensure subsequent Blank Nodes are seen as new. 
	if ( $bnd and  $pt and !$bnew) {
	    $bn++;
	    $bnew=true;}
	// Flag as not a new blank node after listing other predicates or typing something else 
	else if ((!$bnd and $pt) or !$pt) {
	    $bnew=false;}

	// Number each blank node to make it unique							
	if ($bnd)
	  {$trip[0] = $trip[0]."-N".$bn;}
	// Catching reference to a previous blank node
	else if (preg_match("/^(_[bB][a-z]*[ ]*[Nn][a-z]*)[-]([0-9]+)$/", $trip[0], $m))
	  {$trip[0] = "$m[1]-N".($bn-$m[2]);}
				
	// Current process is assuming that the subject and the object can not both be a new Blank Nodes
	if (in_array($trip[2], array("_Blank Node", "_BN", "_bn")))
	  {$trip[2] = $trip[2]."-N".$bn;
	   $bnew=false;}
	else if (preg_match("/^(_[bB][a-z]*[ ]*[Nn][a-z]*)[-]([0-9]+)$/", $trip[2], $m))
	  {$trip[2] = $m[1]."-N".($bn-$m[2]);}

	// Number the predicates so they are all unique									
	$trip[1] = $trip[1]."-N".$tn;
			
	$output[$tag]["triples"][] = $trip;
	$output[$tag]["count"]++;
	}
      else //Empty lines will force a new Blank node to be considered
	{$bnew=false;}
			
      if ($trip[0] == "// Stop") // For debugging
	{break;}
      }

  return ($output);
  }

function formatClassDef ($formats)
  {
  $classDef = false;

  if (isset($formats["default"]))
    {$default = $formats["default"];
     unset($formats["default"]);}
  else
    {$defailts = array();}
    
  foreach ($formats as $nm => $styles)
    {$cda = array();
     $styles = array_merge($default, $styles);
     foreach ($styles as $field => $value)
      {$cda[] = $field.":".$value;}
     $classDef .= "classDef ".trim($nm)." ".implode(",", $cda).";\n";}
     
  return ($classDef);
  }
  
function Mermaid_formatData ($selected)
  {
  global $orientation, $live_edit_link, $config;

  $classDef = formatClassDef ($config["format"]);
  
  ob_start();
  echo <<<END
graph $orientation
$classDef
END;
  $defTop = ob_get_contents();
  ob_end_clean(); // Don't send output to client	

  $defs = "";
  //$defs .= "<div class=\"mermaid\">".$defTop;
  $defs .= $defTop;

  $things = array();
  $no = 0;
  $crm = 0;
  $objs = array();
  
  foreach ($selected["triples"] as $k => $t) 
    {
    // Ensure that all refs to crm classes are unique so the diagram
    // does not Overlap too much
    if(preg_match("/^(crm:E.+)$/", $t[2], $m))
      {$selected["triples"][$k][2] = $t[2]."-".$crm;
       $crm++;}
    // Remove excess white spaces from numbered properties
    if(preg_match("/^(.+)-N[0-9]+$/", $t[1], $m))
      {$selected["triples"][$k][1] = trim($m[1]);}

    $objs[$t[0]] = 1;
    }
		
  foreach ($selected["triples"] as $k => $t) 
    {
    // Format the displayed text, either wrapping or removing numbers
    // used to indicate separate instances of the same text/name
    if (count_chars($t[2]) > 60)
      {$use = wordwrap($t[2], 60, "<br/>", true);}
    else
      {$use = $t[2];}
			
    if(preg_match("/^(crm[:].+)[-][0-9]+$/", $use, $m))
      {$use = $m[1];}

    // Allow the user to force the formatting classes used for the
    // object and subject
    if(isset($t[3]))
      {$fcs = explode ("@@", $t[3]);}
    else
      {$fcs = array(false, false);}
								
    if (!isset($things[$t[0]]))
      {$things[$t[0]] = "O".$no;
       // Default objects to oPID class
       if (!$fcs[0] and !preg_match ("/^[a-zA-Z]+[:].+$/", $t[0], $m))
	{$fcs[0] = "oPID";}
       $defs .= Mermaid_defThing($t[0], $no, $fcs[0]);
       $no++;}
			 
    if (!isset($things[$t[2]]))
      {$things[$t[2]] = "O".$no;
       // Default objects to oPID class
       if (!$fcs[1] and !preg_match ("/^[a-zA-Z]+[:].+$/", $t[1], $m) and isset($objs[$t[2]]))
	{$fcs[1] = "oPID";}
       $defs .= Mermaid_defThing($t[2], $no, $fcs[1]);
       $no++;}		
					 					
    $defs .= $things[$t[0]]." -- ".$t[1]. " -->".$things[$t[2]].
      "[\"".$use."\"]\n";		
    }

  $defs .= ";";
  
  $code = array(
    "code" => $defs,
    "mermaid" => array(
      "theme" => "default",
      "flowchart" => array( 
	  "curve" => "basis")
      ));
  $json = json_encode($code);
  $code = base64_encode($json);
  $live_edit_link = 'https://mermaid-js.github.io/mermaid-live-editor/#/edit/'.$code;
  $defs = "<div class=\"mermaid\">".$defs."</div>";
	
  return ($defs);
  }	

function Mermaid_defThing ($var, $no, $fc=false)
	{
	global $config;

	$prefix = $config["prefix"];
	$click = false;
	$code  = "O".$no;
	$cls = "literal";

	foreach($prefix as $nm => $a)
	  {
	  if(preg_match("/^".$a["match"]["short"]."$/", $var, $m))
	    {
	    $cls = $a["format"] ;
	    if (isset($a["url"]))
	      {$click = "click ".$code." \"".$a["url"]."$m[1]\"\n";}
	    else if(preg_match("/^http.+$/", $var, $m))
	      {$click = "click ".$code." \"".$var."\"\n";}
	    break;
	    }	  
	  }
	if ($fc) {$cls = $fc;}
				 
	$str = "\n$code(\"$var\")\nclass $code $cls;\n".$click;	    
	
	return ($str);
	}

function prg($exit=false, $alt=false, $noecho=false)
	{
	if ($alt === false) {$out = $GLOBALS;}
	else {$out = $alt;}
	
	ob_start();
	echo "<pre class=\"wrap\">";
	if (is_object($out))
		{var_dump($out);}
	else
		{print_r ($out);}
	echo "</pre>";
	$out = ob_get_contents();
  ob_end_clean(); // Don't send output to client
  
	if (!$noecho) {echo $out;}
		
	if ($exit) {exit;}
	else {return ($out);}
	}

function checkTriples ($data)
  {  
  $json = json_decode($data, true);

  if($json)
    {    
    if (isset($json["@context"]))
      {$triplesTxt = "This Model\thas context\t".$json["@context"]."\n";
       unset($json["@context"]);}
    else
      {$triplesTxt = false;}
    
    $triplesTxt .= laj2trips ($json);
    //debugJsonConversaion ($data, $json, $triplesTxt);
    }
  else
    {$triplesTxt = $data;}
    
  return ($triplesTxt);
  }

function laj2trips ($arr, $pSub=false, $pPred=false)
  {
  $out = "";
  
  if (isset($arr["id"]))
    {$sub = $arr["id"];
     unset($arr["id"]);}
  else
    {$sub = "_BN";}
  
  if ($pSub and $pPred)
    {
    $pSub = parseEntities($pSub);
    $sub = parseEntities($sub);
    $out .= "$pSub\t$pPred\t$sub\n";}
    
  foreach ($arr as $k => $v)
    {
    if (is_array($v))
      {
      if(isset($v["id"]))
	{$out .= laj2trips($v, $sub, $k);}
      else
	{foreach ($v as $n => $a)
	  {$out .= laj2trips($a, $sub, $k);}}
      }
    else
      {
      $v = parseEntities($v);
      $sub = parseEntities($sub);
      $out .= "$sub\t$k\t$v\n";
      }
    }

  return($out);
  }

function parseEntities($name)
  {
  if (preg_match("/^http[s]*[:][\/]+vocab[.]getty[.]edu[\/]aat[\/]([0-9]+)$/", $name, $m))
    {$out = "aat:$m[1]";}
  else if (preg_match("/^http[s]*[:][\/]+linked[.]art[\/]example[\/]([a-z]+[\/][0-9]+)$/", $name, $m))
    {$out = "lae:$m[1]";}
  else
    {$out = $name;}

  return($out);
  }

function getRemoteJsonDetails ($uri, $format=false, $decode=false)
	{if ($format) {$uri = $uri.".".$format;}
	 $fc = file_get_contents($uri);
	 if ($decode)
		{$output = json_decode($fc, true);}
	 else
		{$output = $fc;}
	 return ($output);}

?>
