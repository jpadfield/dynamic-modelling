<?php

if ($_POST)//isset($_POST["triplesTxt"]))
  {$triplesTxt = $_POST["triplesTxt"];}
else
  {$triplesTxt = triples ();}
  
$triples = explode("\n", $triplesTxt);
$raw = getRaw($triples);
$mermaid = Mermaid_formatData ($raw["test"]);

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
      <textarea class="form-control rounded-0 detectTab" name="triplesTxt" rows="10">$triplesTxt</textarea>
    </form>
  </div>
      
<div id="holder" class="moddiv">
  <div id="split-container">
      <button class="btn btn-default nav-button" id="fs" onclick="togglefullscreen('fs', 'holder')">Full screen</button>
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
  if ($('#'+b).html() == "Full screen") {
    $('#'+b).html("Restore screen");
    }
  else {
    $('#'+b).html("Full screen");
    }
      
  $('#'+divID).toggleClass('fullscreen');
  }
  

  </script>  
    </body>
</html>

END;
$html = ob_get_contents();
ob_end_clean(); // Don't send output to client

echo $html;

function triples ()
  {
ob_start();
echo <<<END
ng:0F6J-0001-0000-0000	crm:P2.has type	crm:E21.Person	aPID@@crm
ng:0F6J-0001-0000-0000	crm:P2.has type	aat:300411314
aat:300411314	rdfs:label	artist painters@en
ng:0F6J-0001-0000-0000	crm:P2.has type	aat:300024987
aat:300024987	rdfs:label	architechts@en
ng:0F6J-0001-0000-0000	owl:sameAs	ulan:500023578
ulan:500023578	rdfs:label	Raphael@en
ng:0F6J-0001-0000-0000	owl:sameAs	wd:Q5597
wd:Q5597	rdfs:label	Raphael@en
ng:0F6J-0001-0000-0000	rdfs:seeAlso	https://cima.ng-london.org.uk/documentation
https://cima.ng-london.org.uk/documentation	rdfs:label	Raphael Research Resource@en
ng:0F6J-0001-0000-0000	rdfs:comment	Free Text@en
ng:0F6J-0001-0000-0000	crm:P14.performed	ngo:002-0432-0000	aPID@@ePID

_Blank Node	crm:P2.has type	crm:E41.Appellation
ng:0F6J-0001-0000-0000	crm:P131.is identified by	_Blank Node
_Blank Node	rdfs:label	Raphael@en

_Blank Node	crm:P2.has type	crm:E74.Group	aPID@@crm
ng:0F6J-0001-0000-0000	crm:P15.was influenced by	_Blank Node
_Blank Node	owl:sameAs	aat:300107304
aat:300107304	rdfs:label	Ancient Italian@en

_Blank Node	crm:P2.has type	crm:E67 Birth	event@@crm
ng:0F6J-0001-0000-0000	crm:P98.was born	_Blank Node

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
ng:0F6J-0001-0000-0000	crm:P100.died in	_Blank Node

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
ng:0F6J-0001-0000-0000	crm:P70.is documented in	_Blank Node
_Blank Node	owl:sameAs	https://cima.ng-london.org.uk/documentation/files/2009/10/01/Raphael%20Catalogue%20Complete.pdf
_Blank Node	rdfs:seeAlso	https://www.book-info.com/isbn/1-85709-999-0.htm
_Blank Node	rdfs:label	Raphael: From Urbino to Rome@en
END;
$triples = ob_get_contents();
ob_end_clean(); // Don't send output to client

return ($triples);
  }

  
function graphA ()
  {
ob_start();
echo <<<END
graph LR

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

O0("ng:0F6J-0001-0000-0000")
class O0 aPID;
click O0 "http://data.ng-london.org.uk/0F6J-0001-0000-0000" "Tooltip"

O1("crm:E21.Person-0")
class O1 crm;
O0 -- crm:P2.has type -->O1["crm:E21.Person"]

O2("aat:300411314")
class O2 type;
click O2 "http://vocab.getty.edu/aat/300411314" "Tooltip"
O0 -- crm:P2.has type -->O2["aat:300411314"]

O3("artist painters@en")
class O3 literal;
O2 -- rdfs:label -->O3["artist painters@en"]

O4("aat:300024987")
class O4 type;
click O4 "http://vocab.getty.edu/aat/300024987" "Tooltip"
O0 -- crm:P2.has type -->O4["aat:300024987"]

O5("architechts@en")
class O5 literal;
O4 -- rdfs:label -->O5["architechts@en"]

O6("ulan:500023578")
class O6 type;
click O6 "http://vocab.getty.edu/ulan/500023578" "Tooltip"
O0 -- owl:sameAs -->O6["ulan:500023578"]

O7("Raphael@en")
class O7 literal;
O6 -- rdfs:label -->O7["Raphael@en"]

O8("wd:Q5597")
class O8 type;
click O8 "https://www.wikidata.org/wiki/Q5597" "Tooltip"
O0 -- owl:sameAs -->O8["wd:Q5597"]
O8 -- rdfs:label -->O7["Raphael@en"]

O9("https://cima.ng-london.org.uk/documentation")
class O9 url;
click O9 "https://cima.ng-london.org.uk/documentation" "Tooltip"
O0 -- rdfs:seeAlso -->O9["https://cima.ng-london.org.uk/documentation"]

O10("Raphael Research Resource@en")
class O10 literal;
O9 -- rdfs:label -->O10["Raphael Research Resource@en"]

O11("Free Text@en")
class O11 literal;
O0 -- rdfs:comment -->O11["Free Text@en"]

O12("ngo:002-0432-0000")
class O12 ePID;
click O12 "http://data.ng-london.org.uk/resource/002-0432-0000" "Tooltip"
O0 -- crm:P14.performed -->O12["ngo:002-0432-0000"]

O13("_Blank Node-N17")
class O13 oPID;

O14("crm:E41.Appellation-1")
class O14 crm;
O13 -- crm:P2.has type -->O14["crm:E41.Appellation"]
O0 -- crm:P131.is identified by -->O13["_Blank Node-N17"]
O13 -- rdfs:label -->O7["Raphael@en"]

O15("_Blank Node-N18")
class O15 oPID;

O16("crm:E74.Group-2")
class O16 crm;
O15 -- crm:P2.has type -->O16["crm:E74.Group"]
O0 -- crm:P15.was influenced by -->O15["_Blank Node-N18"]

O17("aat:300107304")
class O17 type;
click O17 "http://vocab.getty.edu/aat/300107304" "Tooltip"
O15 -- owl:sameAs -->O17["aat:300107304"]

O18("Ancient Italian@en")
class O18 literal;
O17 -- rdfs:label -->O18["Ancient Italian@en"]

O19("_Blank Node-N19")
class O19 oPID;

O20("crm:E67 Birth-3")
class O20 crm;
O19 -- crm:P2.has type -->O20["crm:E67 Birth"]
O0 -- crm:P98.was born -->O19["_Blank Node-N19"]

O21("_Blank Node-N20")
class O21 oPID;

O22("crm:E53 Place-4")
class O22 crm;
O21 -- crm:P2.has type -->O22["crm:E53 Place"]
O19 -- crm:P7.took place at -->O21["_Blank Node-N20"]

O23("tgn:7003994")
class O23 type;
click O23 "http://vocab.getty.edu/tgn/7003994" "Tooltip"
O21 -- owl:sameAs -->O23["tgn:7003994"]

O24("wd:Q2759")
class O24 type;
click O24 "https://www.wikidata.org/wiki/Q2759" "Tooltip"
O21 -- owl:sameAs -->O24["wd:Q2759"]

O25("Urbino (inhabited place)@en")
class O25 literal;
O23 -- rdfs:label -->O25["Urbino (inhabited place)@en"]

O26("_Blank Node-N21")
class O26 oPID;

O27("crm:E52.Time-span-5")
class O27 crm;
O26 -- crm:P2.has type -->O27["crm:E52.Time-span"]

O28("aat:300379244")
class O28 type;
click O28 "http://vocab.getty.edu/aat/300379244" "Tooltip"
O26 -- crm:P2.has type -->O28["aat:300379244"]

O29("years@en")
class O29 literal;
O28 -- rdfs:label -->O29["years@en"]
O19 -- crm:P4.has timespan -->O26["_Blank Node-N21"]

O30("1483-01-01 #xsd:dateTime")
class O30 literal;
O26 -- crm:P82a.begin of the begin -->O30["1483-01-01 #xsd:dateTime"]

O31("1483-12-31 #xsd:dateTime")
class O31 literal;
O26 -- crm:P82a.end of the end -->O31["1483-12-31 #xsd:dateTime"]

O32("1483@en")
class O32 literal;
O26 -- rdfs:label -->O32["1483@en"]

O33("wd:Q6637")
class O33 type;
click O33 "https://www.wikidata.org/wiki/Q6637" "Tooltip"
O26 -- owl:sameAs -->O33["wd:Q6637"]

O34("_Blank Node-N22")
class O34 oPID;

O35("crm:E69 Death-6")
class O35 crm;
O34 -- crm:P2.has type -->O35["crm:E69 Death"]
O0 -- crm:P100.died in -->O34["_Blank Node-N22"]

O36("_Blank Node-N23")
class O36 oPID;

O37("crm:E53 Place-7")
class O37 crm;
O36 -- crm:P2.has type -->O37["crm:E53 Place"]
O34 -- crm:P7.took place at -->O36["_Blank Node-N23"]

O38("tgn:7000874")
class O38 type;
click O38 "http://vocab.getty.edu/tgn/7000874" "Tooltip"
O36 -- owl:sameAs -->O38["tgn:7000874"]

O39("wd:Q220")
class O39 type;
click O39 "https://www.wikidata.org/wiki/Q220" "Tooltip"
O36 -- owl:sameAs -->O39["wd:Q220"]

O40("Rome (inhabited place)@en")
class O40 literal;
O38 -- rdfs:label -->O40["Rome (inhabited place)@en"]

O41("_Blank Node-N24")
class O41 oPID;

O42("crm:E52.Time-span-8")
class O42 crm;
O41 -- crm:P2.has type -->O42["crm:E52.Time-span"]
O41 -- crm:P2.has type -->O28["aat:300379244"]
O34 -- crm:P4.has timespan -->O41["_Blank Node-N24"]

O43("1520-01-01 #xsd:dateTime")
class O43 literal;
O41 -- crm:P82a.begin of the begin -->O43["1520-01-01 #xsd:dateTime"]

O44("1520-12-31 #xsd:dateTime")
class O44 literal;
O41 -- crm:P82a.end of the end -->O44["1520-12-31 #xsd:dateTime"]

O45("1520@en")
class O45 literal;
O41 -- rdfs:label -->O45["1520@en"]

O46("wd:Q6284")
class O46 type;
click O46 "https://www.wikidata.org/wiki/Q6284" "Tooltip"
O41 -- owl:sameAs -->O46["wd:Q6284"]

O47("_Blank Node-N25")
class O47 oPID;

O48("crm:E31 Document-9")
class O48 crm;
O47 -- crm:P2.has type -->O48["crm:E31 Document"]
O0 -- crm:P70.is documented in -->O47["_Blank Node-N25"]

O49("https://cima.ng-london.org.uk/documentation/files/2009/10/01/Raphael%20Catalogue%20Complete.pdf")
class O49 url;
click O49 "https://cima.ng-london.org.uk/documentation/files/2009/10/01/Raphael%20Catalogue%20Complete.pdf" "Tooltip"
O47 -- owl:sameAs -->O49["https://cima.ng-london.org.uk/documentation/files/2009/10/01<br/>/Raphael%20Catalogue%20Complete.pdf"]

O50("https://www.book-info.com/isbn/1-85709-999-0.htm")
class O50 url;
click O50 "https://www.book-info.com/isbn/1-85709-999-0.htm" "Tooltip"
O47 -- rdfs:seeAlso -->O50["https://www.book-info.com/isbn/1-85709-999-0.htm"]

O51("Raphael: From Urbino to Rome@en")
class O51 literal;
O47 -- rdfs:label -->O51["Raphael: From Urbino to Rome@en"]
;

END;
$code = ob_get_contents();
ob_end_clean(); // Don't send output to client

return ($code);
  }


function getRaw($data)
  {	
  $output = array();
	
  $no = 0;
  $bn = 0;
  $tn = 0;
  $ono = 0;
  $bnew = false;
  $bba = array();
  $bbano = 1;
 
  //foreach ($data as $tag => $arr) 
    //{
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
		
      if (isset($trip[2]))
	{
	// Ignore comments and empty lines

	//All Blank Nodes need to be numbered to be unique
	if ($trip[0] == "_Blank Node" and $trip[1] == "crm:P2.has type" and !$bnew)
	  {$bn++;
	   $bnew=true;}
		
	// Ensure subsequent Blank Nodes are seen as new. 
	if ($trip[1] == "crm:P2.has type" AND $trip[0] != "_Blank Node")
	  {$bnew=false;}
								
	if ($trip[0] == "_Blank Node")
	  {$trip[0] = "_Blank Node-N".$bn;}
	else if (preg_match("/^_Blank Node[-]([0-9]+)$/", $trip[0], $m))
	  {$trip[0] = "_Blank Node-N".($bn-$m[1]);}
				
	// Current process is assuming that the subject and the object can not both be Blank Nodes
	if ($trip[2] == "_Blank Node")
	  {$trip[2] = "_Blank Node-N".$bn;
	   $bnew=false;}
	else if (preg_match("/^_Blank Node[-]([0-9]+)$/", $trip[2], $m))
	  {$trip[2] = "_Blank Node-N".($bn-$m[1]);}
										
	$trip[1] = $trip[1]."-N".$tn;
			
	$output[$tag]["triples"][] = $trip;
	$output[$tag]["count"]++;
	}
      else //Empty lines will force a new Blank node to be considered
	{$bnew=false;}
			
      if ($trip[0] == "// Stop") // For debugging
	{break;}
      }
    //}	

  return ($output);
  }


function Mermaid_formatData ($selected)
  {
  ob_start();
  echo <<<END

graph LR

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

?>
