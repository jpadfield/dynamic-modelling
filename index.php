<?php

$orientation = "LR";

if ($_POST)//isset($_POST["triplesTxt"]))
  {$triplesTxt = checkTriples ($_POST["triplesTxt"]);}
else
  {$triplesTxt = triples ();}

$config = getRemoteJsonDetails ("config.json", false, true);
  
$triples = explode("\n", $triplesTxt);
$raw = getRaw($triples);
$mermaid = Mermaid_formatData ($raw["test"]);

$html = buildPage ($triplesTxt, $mermaid);
echo $html;
exit;

////////////////////////////////////////////////////////////////////////

function buildPage ($triplesTxt, $mermaid)
  {  
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
      <button class="btn btn-default nav-button" style="margin-bottom: 16px;" type="submit">Update</button>
      <button class="btn btn-default nav-button" style="margin-bottom: 16px;" id="clear" type="button">Clear</button>
      <textarea class="form-control rounded-0 detectTab" id="triplesTxt" name="triplesTxt" rows="10">$triplesTxt</textarea>
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
        
  <script src="https://unpkg.com/jquery@3.4.1/dist/jquery.min.js"></script>	<script src="https://unpkg.com/tether@1.4.7/dist/js/tether.min.js"></script>
  <script src="https://unpkg.com/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/mermaid@8.5.2/dist/mermaid.min.js"></script>
  <script>

$(document).ready(function(){
    $("#clear").click(function(){
        $("#triplesTxt").text("")
    });

});

$(function () {

  $(document).on('keydown', 'textarea.detectTab', function(e) { 
    var keyCode = e.keyCode || e.which; 

    if (keyCode == 9) { 
      e.preventDefault(); 
    // call custom function here
    var start = this.selectionStart;
    var end = this.selectionEnd;

    // set textarea value to: text before caret + tab + text after caret
    $(this).val($(this).val().substring(0, start)
                + "\t"
                + $(this).val().substring(end));

    // put caret at right position again
    this.selectionStart =
    this.selectionEnd = start + 1;    
    } 
  });
});

mermaid.initialize({startOnLoad:true, flowchart: { 
    curve: 'basis'
  }});

function togglefullscreen (b, divID)
  {
  var src = $('#'+b).children('img')[0].src;
  var filename = src.substring(src.lastIndexOf('/')+1);

  if (filename == "view-fullscreen.png") {
    $('#'+b).html("<img src=\"graphics/view-restore.png\" width=\"20\" />"); }
  else {
    $('#'+b).html("<img src=\"graphics/view-fullscreen.png\" width=\"20\" />");  }
      
  $('#'+divID).toggleClass('fullscreen');
  $(':focus').blur();
  }
  

  </script>  
    </body>
</html>

END;
$html = ob_get_contents();
ob_end_clean(); // Don't send output to client

return($html);
}

function triples ()
  {
ob_start();
echo <<<END
ng:0QCD-0001-0000-0000	crm:P2.has type	crm:E21.Person	aPID@@crm
ng:0QCD-0001-0000-0000	crm:P2.has type	aat:300411314
aat:300411314	rdfs:label	artist painters@en
ng:0QCD-0001-0000-0000	crm:P2.has type	aat:300024987
aat:300024987	rdfs:label	architechts@en
ng:0QCD-0001-0000-0000	owl:sameAs	ulan:500023578
ulan:500023578	rdfs:label	Raphael@en
ng:0QCD-0001-0000-0000	owl:sameAs	wd:Q5597
wd:Q5597	rdfs:label	Raphael@en
ng:0QCD-0001-0000-0000	rdfs:seeAlso	https://cima.ng-london.org.uk/documentation
https://cima.ng-london.org.uk/documentation	rdfs:label	Raphael Research Resource@en
ng:0QCD-0001-0000-0000	rdfs:comment	Free Text@en
ng:0QCD-0001-0000-0000	crm:P14.performed	ngo:002-0432-0000	aPID@@ePID

_Blank Node	crm:P2.has type	crm:E41.Appellation
ng:0QCD-0001-0000-0000	crm:P131.is identified by	_Blank Node
_Blank Node	rdfs:label	Raphael@en

_Blank Node	crm:P2.has type	crm:E74.Group	aPID@@crm
ng:0QCD-0001-0000-0000	crm:P15.was influenced by	_Blank Node
_Blank Node	owl:sameAs	aat:300107304
aat:300107304	rdfs:label	Ancient Italian@en

_Blank Node	crm:P2.has type	crm:E67 Birth	event@@crm
ng:0QCD-0001-0000-0000	crm:P98.was born	_Blank Node

_Blank Node	crm:P2.has type	crm:E53 Place
_Blank Node-1	crm:P7.took place at	_Blank Node
_Blank Node	owl:sameAs	tgn:7003994
_Blank Node	owl:sameAs	wd:Q2759
tgn:7003994	rdfs:label	Urbino (inhabited place)@en

_Blank Node	crm:P2.has type	crm:E52.Time-span
_Blank Node	crm:P2.has type	aat:300379244
aat:300379244	rdfs:label	years@en
_Blank Node-2	crm:P4.has timespan	_Blank Node
_Blank Node	crm:P82a.begin of the begin	1483-01-01 #xsd:dateTime
_Blank Node	crm:P82a.end of the end	1483-12-31 #xsd:dateTime
_Blank Node	rdfs:label	1483@en
_Blank Node	owl:sameAs	wd:Q6637

_Blank Node	crm:P2.has type	crm:E69 Death	event@@crm
ng:0QCD-0001-0000-0000	crm:P100.died in	_Blank Node

_Blank Node	crm:P2.has type	crm:E53 Place
_Blank Node-1	crm:P7.took place at	_Blank Node
_Blank Node	owl:sameAs	tgn:7000874
_Blank Node	owl:sameAs	wd:Q220
tgn:7000874	rdfs:label	Rome (inhabited place)@en

_Blank Node	crm:P2.has type	crm:E52.Time-span
_Blank Node	crm:P2.has type	aat:300379244
_Blank Node-2	crm:P4.has timespan	_Blank Node
_Blank Node	crm:P82a.begin of the begin	1520-01-01 #xsd:dateTime
_Blank Node	crm:P82a.end of the end	1520-12-31 #xsd:dateTime
_Blank Node	rdfs:label	1520@en
_Blank Node	owl:sameAs	wd:Q6284

_Blank Node	crm:P2.has type	crm:E31 Document
ng:0QCD-0001-0000-0000	crm:P70.is documented in	_Blank Node
_Blank Node	owl:sameAs	https://cima.ng-london.org.uk/documentation/files/2009/10/01/Raphael%20Catalogue%20Complete.pdf
_Blank Node	rdfs:seeAlso	https://www.book-info.com/isbn/1-85709-999-0.htm
_Blank Node	rdfs:label	Raphael: From Urbino to Rome@en
END;
$triples = ob_get_contents();
ob_end_clean(); // Don't send output to client

return ($triples);
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

      if(preg_match("/^[\/][\/][ ]*[gG]raph[ ]*([LT][BR])(.*)$/", $line, $m))
	{$orientation = $m[1];}

      // Ignore comments and empty lines
      if (isset($trip[2]))
	{
	if (in_array($trip[0], array("_Blank Node", "_BN", "_bn")))
	  {$bnd = true;}
	else
	  {$bnd = false;}

	if (in_array ($trip[1], array("crm:P2.has type", "has type", "type", "rdf:type")))
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


function Mermaid_formatData ($selected)
  {
  global $orientation;
  
  ob_start();
  echo <<<END

graph $orientation

classDef crm stroke:#333333,fill:#DCDCDC,color:#333333,rx:5px,ry:5px;
classDef thing stroke:#2C5D98,fill:#D0E5FF,color:#2C5D98,rx:5px,ry:5px;
classDef event stroke:#6B9624,fill:#D0DDBB,color:#6B9624,rx:5px,ry:5px;
classDef oPID stroke:#2C5D98,fill:#2C5D98,color:white,rx:5px,ry:5px;
classDef ePID stroke:#6B9624,fill:#6B9624,color:white,rx:5px,ry:5px;
classDef aPID stroke:black,fill:#FFFF99,rx:20px,ry:20px;
classDef type stroke:red,fill:#B51511,color:white,rx:5px,ry:5px;
classDef name stroke:orange,fill:#FEF3BA,rx:20px,ry20px;
classDef literal stroke:black,fill:#FFB975,rx:2px,ry:2px,max-width:100px;
classDef classstyle stroke:black,fill:white;
classDef url stroke:#2C5D98,fill:white,color:#2C5D98,rx:5px,ry:5px;
classDef note stroke:#2C5D98,fill:#D8FDFF,color:#2C5D98,rx:5px,ry:5px;

END;
  $defTop = ob_get_contents();
  ob_end_clean(); // Don't send output to client	

  $defs = "";
  $defs .= "<div class=\"mermaid\">".$defTop;

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

  $defs .= ";</div>";
	
  return ($defs);
  }	

function Mermaid_defThing ($var, $no, $fc=false)
	{	
	$diagCmatches = array(
		"aat[:].+" => "type",
		"wd[:].+" => "type",
		"ulan[:].+" => "type",
		"tgn[:].+" => "type",
		"ng[:].+" => "oPID",
		"ngo[:].+" => "oPID",
		"ngi[:].+" => "oPID",
		"_Blank.+" => "thing",
		"http.+" => "url",
		"crm[:]E.+" => "crm",
		"[\"].+[\"]" => "note"
		);
		 
	if ($fc) {$cls = $fc;}
	else {
		$cls = "literal";
		foreach ($diagCmatches as $k => $cur)
			{
			if(preg_match("/^".$k."$/", $var, $m))
				{$cls = $cur;
				 break;}}}	 
	$code  = "O".$no;
	$str = "\n$code(\"$var\")\nclass $code $cls;\n";
		 
	if(preg_match("/^http.+$/", $var, $m))
		{$str .= "click ".$code." \"$var\" \"Tooltip\"\n";}		
	else if(preg_match("/^ngo[:]([0-9A-Z]{3}[-].+)$/", $var, $m))
		{$str .= "click ".$code." \"http://data.ng-london.org.uk/resource/$m[1]\" \"Tooltip\"\n";}
	else if(preg_match("/^ng[:]([0-9A-Z]{4}[-].+)$/", $var, $m))
		{$str .= "click ".$code." \"http://data.ng-london.org.uk/$m[1]\" \"Tooltip\"\n";}
	else if(preg_match("/^aat[:](.+)$/", $var, $m))
		{$str .= "click ".$code." \"http://vocab.getty.edu/aat/$m[1]\" \"Tooltip\"\n";}
	else if(preg_match("/^tgn[:](.+)$/", $var, $m))
		{$str .= "click ".$code." \"http://vocab.getty.edu/tgn/$m[1]\" \"Tooltip\"\n";}
	else if(preg_match("/^ulan[:](.+)$/", $var, $m))
		{$str .= "click ".$code." \"http://vocab.getty.edu/ulan/$m[1]\" \"Tooltip\"\n";}
	else if(preg_match("/^wd[:](.+)$/", $var, $m))
		{$str .= "click ".$code." \"https://www.wikidata.org/wiki/$m[1]\" \"Tooltip\"\n";}
	
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
      {foreach ($v as $n => $a)
	{$out .= laj2trips($a, $sub, $k);}}
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
