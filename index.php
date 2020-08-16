<?php

$orientation = "LR";
$live_edit_link = "./";
$default = file_get_contents("default.csv");
$config = getRemoteJsonDetails ("config.json", false, true);
$examples = getRemoteJsonDetails ("examples.json", false, true);
$usedClasses = array();
$allClasses = formatClassDef ($config["format"]);

if (isset($_POST["triplesTxt"]) and $_POST["triplesTxt"])
  {$triplesTxt = checkTriples ($_POST["triplesTxt"]);}
else if (isset($_GET["example"]) and isset($examples[$_GET["example"]]))
  {$ex = $examples[$_GET["example"]];
  if (isset($ex["data"]))
    {$triplesTxt = gzuncompress(base64_decode(urldecode($ex["data"])));}
  else
    {$triplesTxt = checkTriples (file_get_contents($ex["uri"]));}}
else if (isset($_GET["url"]))
  {$triplesTxt = checkTriples (file_get_contents($_GET["url"]));}
else if (isset($_GET["data"]))
  {$triplesTxt = gzuncompress(base64_decode($_GET["data"]));}
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
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenuExamples" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Examples
    </a>
  <div class="dropdown-menu  dropdown-menu-right" aria-labelledby="dropdownMenuExamples">
END;
  $html = ob_get_contents();
  ob_end_clean(); // Don't send output to client

  foreach ($examples as $k => $a)
    {$html .= "<a class=\"dropdown-item\" href=\"./?example=$k\">$a[title]</a>\n";}

  $html .= "</div></li>";

  return ($html);
  }
  
function buildLinksDD ()
  {
  global $live_edit_link, $bookmark;

  ob_start(); //style="margin-right: 8px; float:right; margin-bottom: 16px;" 
  echo <<<END
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenuLinks" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Links
    </a>
  <div class="dropdown-menu  dropdown-menu-right" aria-labelledby="dropdownMenuLinks">
    <a class="dropdown-item" title="Mermaid Live Editor" href=" $live_edit_link" target="_blank">Get Image</a>
    <a class="dropdown-item" title="Bookmark Link" href=" $bookmark" target="_blank">Bookmark Link</a>
END;
  $html = ob_get_contents();
  ob_end_clean(); // Don't send output to client

  foreach ($examples as $k => $a)
    {$html .= "<a class=\"dropdown-item\" href=\"./?example=$k\">$a[title]</a>\n";}

  $html .= "</div></li>";

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
  $links = buildLinksDD ();
  $modal = buildModal ();
  ob_start();
  echo <<<END

<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta charset="utf-8">
  <title>Dynamic Simple Modelling</title>
  <link href="https://unpkg.com/bootstrap@4.5.0/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="local.css" rel="stylesheet" type="text/css">
  <style></style>
</head>
<body>
<div id="page" class="container-fluid">

  <div class="d-flex flex-column mb-3 vh-100">
    <!-- LEVEL 1 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">

      <a title="GitHub Dynamic Modelling" href="https://github.com/jpadfield/dynamic-modelling"  target="_blank"  class="imbutton" style="float:right;" >
	<img alt="GitHub Logo" aria-label="GitHub Logo" src="graphics/GitHub-Mark-64px.png" width="32" /></a>
	
      <h1 class="navbar-brand" style="font-size:1.5rem;margin:0px 16px 0px 16px;">Simple Dynamic Modelling</h1>
      
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span></button>
      
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
	<ul class="navbar-nav ml-auto">
	  $exms
	  $links
	  <li class="nav-item">
	    <a href="#myModal" data-toggle="modal" data-target="#helpModalCenter" class="nav-link">Help</a></li>
	</ul>
      </div>
      
    </nav> <!-- CLOSE LEVEL 1 -->
    <!-- LEVEL 2 -->
    <div class="" style=""  role="region" >
      <form id="triplesFrom" action="./" method="post">
	<div  id="textholder" class="textareadiv form-group flex-grow-1 d-flex flex-column">
	  <textarea class="form-control flex-grow-1 rounded-0 detectTab" id="triplesTxt" name="triplesTxt"  style="overflow-y:scroll;" aria-label="Textarea for triples" rows="10">$triplesTxt</textarea>
	  <div class="tbtns" style="">
	      <button title="Refresh Model" class="btn btn-default textbtn" id="refreshM" type="submit"  aria-label="Refresh Model"><img aria-label="Refresh Model"  alt="Refresh Model" src="graphics/view-refresh.png" width="20" /></button>
	      <button title="Clear Text" class="btn btn-default textbtn" id="clear" type="button"  aria-label="Clear Textarea"><img aria-label="Clear Text" alt="Clear Text" src="graphics/clear-text.png" width="20" /></button>
	      <button title="Help" class="btn btn-default textbtn" id="help" type="button"   data-toggle="modal" data-target="#helpModalCenter" aria-label="Open Help Modal"><img alt="Help" aria-label="Help" src="graphics/help.png" width="20" /></button>
	      <button title="Toggle Fullscreen" class="btn btn-default textbtn" id="tfs" type="button"  aria-label="Toggle Textarea Full-screen" onclick="togglefullscreen('tfs', 'textholder')"><img alt="Toggle Fullscreen" aria-label="Toggle Fullscreen" src="graphics/view-fullscreen.png" width="20" /></button>
	  </div>
	</div>
      </form>
    </div><!-- CLOSE LEVEL 2 -->
    <!-- LEVEL 3 -->
    <div  role="main" aria-label="Holder for the actual flow diagram model"  id="holder" class="flex-grow-1 moddiv">
	<div class="tbtns" style="">
	    <button class="btn btn-default nav-button textbtn" id="fs"  aria-label="Toggle Model Full-screen"  style="top:0px;left:0px;" onclick="togglefullscreen('fs', 'holder')"><img   alt="Toggle Fullscreen"  aria-label="Toggle Fullscreen" src="graphics/view-fullscreen.png" width="20" /></button></div>
	<div style="overflow: scroll; height: 100%;" tabindex=0>
	$mermaid
	</div>
    </div><!-- CLOSE LEVEL 3 -->
  </div><!-- CLOSE FLEX DIV -->
$modal
</div><!-- CLOSE PAGE -->
      
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


function buildModal ()
  {
  // Based on https://bbbootstrap.com/snippets/modal-multiple-tabs-89860645
  $tabs = array(
    "Summary" => ' ',
    "Blank Nodes" => ' ',
    "Formatting" => ' ',
    "Aliases" => ''
    );

  $tHeaders = false;
  $tFields = false;

  $at = " active";
  $sh = "show";
  $tc = "font-weight-bold";

  $no = 1;
  foreach ($tabs as $k => $ht)
    {
    $dno = sprintf('%02d', $no);
    $tHeaders .= "
<div class=\"tabs$at\" id=\"tab$dno\">".
      "<h6 class=\"$tc\">$k</h6></div>";

    $tFields .= "
<fieldset id=\"tab${dno}1\"  class=\"$sh\"><div class=\"bg-light\">
  <h5 class=\"text-center mb-4 mt-0 pt-4\">$k</h5>
  $ht
</div></fieldset>";

    $at = "";
    $sh = "";
    $tc = "text-muted";
    $no++;
    }
  
  ob_start();
  echo <<<END
  <!-- Modal-->
  <div id="helpModalCenter" tabindex="-1" role="dialog" aria-label="Help Modal" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
	<!-- Tab headers, numbered from tab01 -> tab0n, etc -->
	<div class="modal-header row d-flex justify-content-between mx-1 mx-sm-3 mb-0 pb-0 border-0">
	      $tHeaders
	</div>
	<div class="line"></div>
	<!-- Tab Contents, numbered from tab011 -> tab0n1, etc -->
	<div class="modal-body p-0">
	  $tFields
        </div>
        <div class="line"></div>
        <div class="modal-footer d-flex flex-column justify-content-center border-0">
	  <p class="text-muted">More questions or issues? - <a href="https://github.com/jpadfield/dynamic-modelling/issues">Try Github</a>.</p>
	</div>
      </div>
    </div>
  </div>
END;
  $html = ob_get_contents();
  ob_end_clean(); // Don't send output to client

  return ($html);
  }


  
function buildModalDefault()
  {
  // Based on https://bbbootstrap.com/snippets/modal-multiple-tabs-89860645
  $tabs = array(
    "My Apps" => ' <h5 class="text-center mb-4 mt-0 pt-4">My Apps</h5>
                                <h6 class="px-3">Most Used Apps</h6>
                                <ol class="pb-4">
                                    <li>Watsapp</li>
                                    <li>Instagram</li>
                                    <li>Chrome</li>
                                    <li>Linkedin</li>
                                </ol>
                            </div>
                            <div class="px-3">
                                <h6 class="pt-3 pb-3 mb-4 border-bottom"><span class="fa fa-android"></span> Suggested Apps</h6>
                                <h6 class="text-primary pb-2"><a href="#">Opera Browser</a> <span class="text-secondary">- One of the best browsers</span></h6>
                                <h6 class="text-primary pb-2"><a href="#">Camscanner</a> <span class="text-secondary">- Easily scan your documents</span></h6>
                                <h6 class="text-primary pb-4"><a href="#">Coursera</a> <span class="text-secondary">- Learn online, lecturers from top universities</span></h6>',
    "Knowledge Center" => '<h5 class="text-center mb-4 mt-0 pt-4">Knowledge Center</h5>
                                <form>
                                    <div class="form-group pb-5 px-3"> <select name="account" class="form-control">
                                            <option selected disabled>Select Product</option>
                                            <option>Product 1</option>
                                            <option>Product 2</option>
                                            <option>Product 3</option>
                                            <option>Product 4</option>
                                        </select> </div>
                                </form>
                            </div>
                            <div class="px-3">
                                <h6 class="pt-3 pb-3 mb-4 border-bottom"><span class="fa fa-star"></span> Popular Topics</h6>
                                <h6 class="text-primary pb-2"><a href="#">Getting started with Blazemeter</a></h6>
                                <h6 class="text-primary pb-2"><a href="#">Creating tests</a></h6>
                                <h6 class="text-primary pb-4"><a href="#">Running tests</a></h6>',
    "Communities" => ' <h5 class="text-center mb-4 mt-0 pt-4">Communities</h5>
                                <form>
                                    <div class="form-group pb-5 px-3 row justify-content-center"> <button type="button" class="btn btn-primary">New Community +</button> </div>
                                </form>
                            </div>
                            <div class="px-3">
                                <div class="border border-1 box">
                                    <h5>Community 1</h5>
                                    <p class="text-muted mb-1">Members : <strong>27</strong></p>
                                </div>
                                <div class="border border-1 box">
                                    <h5>Community 2</h5>
                                    <p class="text-muted mb-1">Members : <strong>16</strong></p>
                                </div>',
    "Education" => ' <h5 class="text-center mb-4 mt-0 pt-4">Education</h5>
                                <form>
                                    <div class="form-group pb-2 px-3"> <input type="text" placeholder="Enter College Name" class="form-control"> </div>
                                    <div class="form-group row pb-2 px-3">
                                        <div class="col-6"> <input type="text" placeholder="Percentage" class="form-control"> </div>
                                        <div class="col-6"> <input type="text" placeholder="Grade" class="form-control"> </div>
                                    </div>
                                    <div class="form-group px-3 pb-2"> <label class="form-control-label">
                                            <h6>What are you good at ?</h6>
                                        </label>
                                        <div class="custom-control custom-checkbox"> <input class="custom-control-input" id="option1" type="checkbox" value=""> <label class="custom-control-label" for="option1">Web Development</label> </div>
                                        <div class="custom-control custom-checkbox"> <input class="custom-control-input" id="option2" type="checkbox" value=""> <label class="custom-control-label" for="option2">Data Structures & Algorithms</label> </div>
                                        <div class="custom-control custom-checkbox"> <input class="custom-control-input" id="option3" type="checkbox" value=""> <label class="custom-control-label" for="option3">Android Development</label> </div>
                                        <div class="custom-control custom-checkbox"> <input class="custom-control-input" id="option4" type="checkbox" value=""> <label class="custom-control-label" for="option4">Blockchain</label> </div>
                                        <div class="custom-control custom-checkbox"> <input class="custom-control-input" id="option5" type="checkbox" value=""> <label class="custom-control-label" for="option5">Machine Learning Algorithms</label> </div>
                                    </div>
                                    <div class="form-group pb-5 row justify-content-center"> <button type="button" class="btn btn-primary px-3">Submit</button> </div>
                                </form>
                            </div>
                            <div class="px-3">
                                <h6 class="pt-3 pb-3 mb-4 border-bottom"><span class="fa fa-rocket"></span> Trending Technologies</h6>
                                <h6 class="text-primary pb-2"><a href="#">Augmented Reality and Virtual Reality</a></h6>
                                <h6 class="text-primary pb-2"><a href="#">Angular and React</a></h6>
                                <h6 class="text-primary pb-2"><a href="#">Big Data and Hadoop</a></h6>
                                <h6 class="text-primary pb-4"><a href="#">Internet of Things (IoT)</a></h6>',
    );

  $tHeaders = false;
  $tFields = false;

  $at = " active";
  $sh = "show";
  $tc = "font-weight-bold";

  $no = 1;
  foreach ($tabs as $k => $ht)
    {
    $dno = sprintf('%02d', $no);
    $tHeaders .= "
<div class=\"tabs$at\" id=\"tab$dno\">".
      "<h6 class=\"$tc\">$k</h6></div>";

    $tFields .= "
<fieldset id=\"tab${dno}1\"  class=\"$sh\"><div class=\"bg-light\">
  $ht
</div></fieldset>";

    $at = "";
    $sh = "";
    $tc = "text-muted";
    $no++;
    }
  
  ob_start();
  echo <<<END
  <!-- Modal-->
  <div id="helpModalCenter" tabindex="-1" role="dialog" aria-labelledby="helpModalCenterTitle" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
	<!-- Tab headers, numbered from tab01 -> tab0n, etc -->
	<div class="modal-header row d-flex justify-content-between mx-1 mx-sm-3 mb-0 pb-0 border-0">
	      $tHeaders
	</div>
	<div class="line"></div>
	<!-- Tab Contents, numbered from tab011 -> tab0n1, etc -->
	<div class="modal-body p-0">
	  $tFields
        </div>
        <div class="line"></div>
        <div class="modal-footer d-flex flex-column justify-content-center border-0">
	  <p class="text-muted">Can't find what you're looking for?</p> <button type="button" class="btn btn-primary">Contact Support Team</button>
	</div>
      </div>
    </div>
  </div>
END;
  $html = ob_get_contents();
  ob_end_clean(); // Don't send output to client

  return ($html);
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
  $allClasses = array();
  $classDef = false;

  if (isset($formats["base"]))
    {$default = $formats["base"];}
  else
    {$default = array();}

  // additional historic classes
  $formats["oPID"] = $formats["object"];
  $formats["ePID"] = $formats["event"];
  $formats["aPID"] = $formats["actor"];
    
  foreach ($formats as $nm => $styles)
    {$cda = array();
     $styles = array_merge($default, $styles);
     foreach ($styles as $field => $value)
      {$cda[] = $field.":".$value;}
     $classDef .= "classDef ".trim($nm)." ".implode(",", $cda).";\n";
     $allClasses[trim($nm)] = "classDef ".trim($nm)." ".implode(",", $cda).";\n";}
     
  return ($allClasses);//$classDef);
  }
  
function Mermaid_formatData ($selected)
  {
  global $orientation, $live_edit_link, $config, $usedClasses;
	
  $defs = "";
  $things = array();
  $no = 0;
  $unique = 0;
  $objs = array();
  
  foreach ($selected["triples"] as $k => $t) 
    {
    // Starting things with @ can upset mermaid
    foreach ($t as $tk => $tv)
      {if (preg_match("/^[\@](.+$)/", $tv, $m))
	{$t[$tk] = $m[1];} }
    
    // Ensure that all refs to listed prefix classes are unique so the diagram
    // does not Overlap too much
    
    $au = $config["unique"]["prefix"];

    foreach ($au  as $pk => $pr)
      {if(preg_match("/^($pr:.+)$/", $t[2], $m))
	{$selected["triples"][$k][2] = $t[2]."-".$unique;
	 $unique++;
	 break 1;}}
       
    // Remove excess white spaces from numbered properties
    if(preg_match("/^(.+)-N[0-9]+$/", $t[1], $m))
      {$selected["triples"][$k][1] = trim($m[1]);}

    $objs[$t[0]] = 1;
    }
	
  foreach ($selected["triples"] as $k => $t) 
    {
    // @ at the start of terms breaks the mermaid builder
    foreach ($t as $tk => $tv)
      {if (preg_match("/^[\@](.+$)/", $tv, $m))
	{$t[$tk] = $m[1];} }
	
    // Format the displayed text, either wrapping or removing numbers
    // used to indicate separate instances of the same text/name
    if (count_chars($t[2]) > 60)
      {$use = wordwrap($t[2], 60, "<br/>", true);}
    else
      {$use = $t[2];}
			
    foreach ($au  as $pk => $pr)
      {if(preg_match("/^(${pr}[:].+)[-][0-9]+$/", $use, $m))
	{$use = $m[1];
	 break 1;}}

    // Allow the user to force the formatting classes used for the
    // object and subject
    if(isset($t[3]))
      {$fcs = explode ("|", $t[3]);
	if(!isset($fcs[1]))
	  {$fcs = explode ("@@", $t[3]);}
	if(!isset($fcs[1]))
	  {$fcs[1] = false;}}
    else
      {$fcs = array(false, false);}
								
    if (!isset($things[$t[0]]))
      {$things[$t[0]] = "O".$no;
       // Default objects to object class
       if (!$fcs[0] and !preg_match ("/^[a-zA-Z]+[:].+$/", $t[0], $m))
	{$fcs[0] = "object";}
       $defs .= Mermaid_defThing($t[0], $no, $fcs[0]);
       $no++;}

    //  NEED TO REVISIT THE AUTOMATIC ASSIGNMENT OF object class
    // NEED NEW RULES
    if (!isset($things[$t[2]]))
      {$things[$t[2]] = "O".$no;
       // Default objects to object class
       if (!$fcs[1] and !preg_match ("/^[a-zA-Z]+[:].+$/", $t[2], $m) and isset($objs[$t[2]]))
	{$fcs[1] = "object";}
       $defs .= Mermaid_defThing($t[2], $no, $fcs[1]);
       $no++;}		

    $defs .= $things[$t[0]]." -- ".$t[1]. " -->".$things[$t[2]].
      "[\"".$use."\"]\n";		
    }

  $defs = "graph $orientation\n".
    implode("", $usedClasses).
    "\n$defs;";
  
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
	global $config, $usedClasses, $allClasses;		

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

	if(isset($allClasses[$cls]))
	  {$usedClasses[$cls] = $allClasses[$cls];}
				 
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
    $pPred = parseEntities($pPred);
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
      $k = parseEntities($k);
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
