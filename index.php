<?php

// Added option to link subgraphs as nodes - needs diagram to be set as "flowchart" and not "graph"
// Added tests for hover text - it just uses a default example text just now
// Added the option of fixing the properties tags to the lines or letting them float - add "fix" after //Flowchart LR fix
$versions = array(
  "jquery" => "3.7.0",
  "bootstrap" => "5.3.1",
  "mermaid" => "10.3.0", // 9.2.2 available but it breaks the zoom option, so would need to check.
  "tether" => "2.0.0",
  "pako" => "2.1.0",
  "base64" => "3.7.5"
  );

if (isset($_GET["debug"])) {}
  
if (isset($_SERVER["SCRIPT_URI"]))
  {$thisPage = $_SERVER["SCRIPT_URI"];}
else
  {$thisPage = "./";}

$pako = false;
$diagram = "flowchart";
$fixlinks = false;  
$orientation = "LR";

$default = file_get_contents("default.csv");
$config = getRemoteJsonDetails ("config.json", false, true);
$examples = getRemoteJsonDetails ("examples.json", false, true);
$usedClasses = array();
$subGraphs = array();
$subGraphCount = 0;
$allClasses = formatClassDef ($config["format"]);

$doc_example_links = array(
  "LRNF" => array("TBNF", "LRF", "LR"), 
  "TBNF" => array("LRNF", "TBF", "TB"), 
  "LRF" => array("TBF", "LRNF", "LR fix"), 
  "TBF" => array("LRF", "TBNF", "TB fix"), 
  );

// Expects pako compressed data and pulls image directly from https://mermaid.ink
if (isset($_GET["image"]))
  {getModelImage($_GET["image"]);
	 exit;}  

// Default process of using the tool - receiving data from POST form.
else if (isset($_POST["triplesTxt"]) and $_POST["triplesTxt"])
  {$triplesTxt = checkTriples ($_POST["triplesTxt"]);}
  
// Pulls in prepared data from local examples of defined files
// TODO - local data needs to be updated to pako compression
else if (isset($_GET["example"]) and isset($examples[$_GET["example"]]))
  {
  $ex = $examples[$_GET["example"]];
  
  if (isset($ex["data"]))
    {$triplesTxt = gzuncompress(base64_decode(urldecode($ex["data"])));}
  else
    {$triplesTxt = checkTriples (file_get_contents($ex["uri"]));}
  
  if ($_GET["example"] == "object2")
    {$triplesTxt = "//Flowchart LR fix\n".$triplesTxt;}
  else if ($_GET["example"] == "documentation")
    {$triplesTxt = docExampleTriples ($doc_example_links["LRNF"], $triplesTxt);}
  }
  
// Used to ad additional format options to the default "instructions diagram
else if (isset($_GET["example"]) and isset($doc_example_links[$_GET["example"]]))
  {$triplesTxt = docExampleTriples ($doc_example_links[$_GET["example"]]);}
  
// TODO works with an external data source 
else if (isset($_GET["url"]))
  {$fc = getRemoteURL ($_GET["url"]);
   $triplesTxt = checkTriples ($fc);}
  
// TODO Need to update to allow data to be sent as pako compressed - three options 
// 1: Duplicate pako JavaScript compression (used by MLE) in PHP
// 2: Call local Javascript function via Node JS to preform the compression
// 3: Move all data formatting to AJAX processes and make use of the default pako compression as needed.
// CURRENT PLAN is to follow option 3
else if (isset($_GET["data"]) and preg_match("/^[p][a][k][o][:](.+)$/", $_GET["data"], $m))
  {
	$triplesTxt = "Please wait	tooltip	Processing supplied data";
	$pako = $m[1];//$_GET["data"];
  }
else if (isset($_POST["triples"]))
  {
	$triples = getCleanTriples($_POST["triples"]);
	$cleanTriplesTxt = implode("\n", $triples);
	$raw = getRaw($triples);
	$mermaid = Mermaid_formatData ($raw["test"]);
	
	header('Content-Type: application/json');
	header("Access-Control-Allow-Origin: *");
	echo json_encode(array("triples" => $cleanTriplesTxt, "mermaid" => $mermaid));
	exit;
  }    
// TODO simple PHP based compression option (URLs much longer) want to still have the option as a fall back
else if (isset($_GET["data"]))
  {$triplesTxt = gzuncompress(base64_decode($_GET["data"]));}
  
// Default instructions diagram
else
  {$triplesTxt = docExampleTriples ($doc_example_links["LRNF"]);}

// TODO Thumbnail display in diagram nodes might be possible with - but needs to be re-examined as it was not sorted
//    
// O4 -- "crm:P48_has_preferred_identifier" -->O6[&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<img src='https://research.ng-london.org.uk/iiif/pics/tmp/raphael_pyr/N-1171/08_Images_of_Frames/raphael%20capitals%20right%20and%20left-PYR.tif/full/,125/0/default.jpg'/>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp]


// The text "&nbsp"s are required to get the box to be bigger - it results in a slide like display.
// in 9.1.4 it seems to work without the "&nbsp"s
// O4 -- "crm:P48_has_preferred_identifier" -->O6[<img src='https://research.ng-london.org.uk/iiif/pics/tmp/raphael_pyr/N-1171/08_Images_of_Frames/raphael%20capitals%20right%20and%20left-PYR.tif/full/,125/0/default.jpg'/>];    

// TODO move process into JavaScript with pako compression
$data = urlencode(base64_encode(gzcompress($triplesTxt)));
$bookmark = $thisPage.'?data='.$data;

// TODO move to JavaScript and AJAX processes.
$triples = getCleanTriples($triplesTxt);
$cleanTriplesTxt = implode("\n", $triples);
$raw = getRaw($triples);
$mermaid = Mermaid_formatData ($raw["test"]);
  
$html = buildPage ($cleanTriplesTxt, $mermaid);
echo $html;
exit;

////////////////////////////////////////////////////////////////////////


function docExampleTriples ($ex, $use=false)
  {
  global $default;
  
  if(!$use) {$use = $default;}
  
  $layout_comments = array(
    "TB" => array ("Flowchart TB", "In addition to the default left-right (LR) orientation diagrams can also be arranged from the top-bottom (TB)"),
    "LR" => array ("Flowchart LR", "In addition to the optional top-bottom (TB) orientation diagrams can also be arranged with the default Left-Right (LR) orientation"),
    "F" => array ("Fixed Properties", "This format extends and straightens  the lines linking the various concepts together to ensure there is a flat section of the line for the link property to be specifically fixed to. This can result in a larger overall diagram, but can be required when there are higher numbers of property links being displayed together"),
    "NF" => array ("Relaxed Properties", "This format curves  the lines linking the various concepts together to minimise the size of the generated diagram.")
    );
  
  $triplesTxt = "//Flowchart $ex[2] \n".checkTriples ($use);  

  $do = str_split($ex[0], 2);  
  $triplesTxt .= "\nDynamic Modeller\tcan be formatted with\t".$layout_comments[$do[0]][0]."|https://research.ng-london.org.uk/modelling/?example=$ex[0]";
  $triplesTxt .= "\n".$layout_comments[$do[0]][0]."\thas comment\t".json_encode($layout_comments[$do[0]][1]);
  
  $do = str_split($ex[1], 2);
  $triplesTxt .= "\nDynamic Modeller\tcan be formatted with\t".$layout_comments[$do[1]][0]."|https://research.ng-london.org.uk/modelling/?example=$ex[1]";
  $triplesTxt .= "\n".$layout_comments[$do[1]][0]."\thas comment\t".json_encode($layout_comments[$do[1]][1]);

  return ($triplesTxt);
  }
  
function buildExamplesDD ()
  {
  global $examples;

  ob_start();
  echo <<<END
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenuExamples" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Examples
    </a>
  <div class="dropdown-menu  dropdown-menu-end" aria-labelledby="dropdownMenuExamples">
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
  global $bookmark;

  $date = date('Y-m-d_H-i-s');
  
  ob_start(); //style="margin-right: 8px; float:right; margin-bottom: 16px;" 
  
  // 
  echo <<<END
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenuLinks" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Links
    </a>
  <div class="dropdown-menu  dropdown-menu-end" aria-labelledby="dropdownMenuLinks">
    <a class="dropdown-item" id="downloadLink" title="Mermaid Get PNG" href="" download="model_$date.png">Download Image</a>    
    <!-- <a class="dropdown-item" title="Bookmark Link" href="$bookmark" target="_blank">Bookmark Link</a> -->
		<a class="dropdown-item" id="bookmark" title="Bookmark Link" href="" target="_blank">Bookmark Link</a>
    <a class="dropdown-item" id="mermaidLink" title="Edit further in the Mermaid Live Editor" href="" target="_blank">Mermaid Editor</a>
END;
  $html = ob_get_contents();
  ob_end_clean(); // Don't send output to client

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
  global  $thisPage, $versions, $pako;
  
  $exms = buildExamplesDD ();
  $links = buildLinksDD ();
  $modal = buildModal ();
  
  $code = array(
    "code" => $mermaid,
    "mermaid" => array(
      "theme" => "default",
      //"securityLevel" => "loose", This option forces an alert in the live editor
      "logLevel" => "warn",
      "flowchart" => array( 
    "curve" => "basis",
    "htmlLabels" => true)
      ));
 
  $json_code = json_encode($code);

  $bw = "26px";
  
  $vs[0] = $versions["bootstrap"];
  $vs[1] = $versions["jquery"];
  $vs[2] = $versions["bootstrap"];
  $vs[3] = $versions["mermaid"];
  $vs[4] = $versions["tether"];
  $vs[5] = $versions["pako"];
  $vs[6] = $versions["base64"];
  
  $jslib = "https://unpkg.com";
  $jslib = "https://cdn.jsdelivr.net/npm";
  ob_start();
  echo <<<END

<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta charset="utf-8">
  <title>Dynamic Simple Modelling</title>
  <link href="$jslib/bootstrap@$vs[0]/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="css/local.css" rel="stylesheet" type="text/css">
  <style>
  

/* Added to get the hover texts or tooltips to appear and be formatted.
based on values in https://unpkg.com/browse/mermaid@6.0.0/dist/mermaid.css */
div.mermaidTooltip {
  position: absolute;
  text-align: center;
  max-width: 300px;
  padding: 5px;
  font-family: 'trebuchet ms', verdana, arial;
  font-size: 1rem;
  background: #ffffde;
  border: 1px solid #aaaa33;
  border-radius: 5px;
  pointer-events: none;
  z-index: 10000;
}

  
  
  </style>
  <!-- Global site tag (gtag.js) - Google Analytics 
<script async src="https://www.googletagmanager.com/gtag/js?id=G-P2QQWTBKX7"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-P2QQWTBKX7');
</script>-->
</head>
<body>
<div id="page" class="container-fluid">

  <div class="d-flex flex-column mb-3 vh-100">
    <!-- LEVEL 1 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">

      <a title="GitHub Dynamic Modelling" href="https://github.com/jpadfield/dynamic-modelling"  target="_blank"  class="imbutton" style="float:right;" >
  <img alt="GitHub Logo" aria-label="GitHub Logo" src="graphics/GitHub-Mark-64px.png" style="margin-left:10px;" width="32" /></a>
  
      <h1 class="navbar-brand" style="font-size:1.5rem;margin:0px 16px 0px 16px;">Simple Dynamic Modelling</h1>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span></button>
      
      <div class="collapse navbar-collapse float-end" id="navbarSupportedContent">
      
      <span class="navbar-text w-100">
      
  <ul class="navbar-nav ml-auto float-end">
    $exms
    $links
    <li class="nav-item">
      <a href="#myModal" data-bs-toggle="modal" data-bs-target="#helpModalCenter" class="nav-link me-4">Info</a></li>
  </ul>
  </span>
      </div>
      
    </nav> <!-- CLOSE LEVEL 1 -->
    <!-- LEVEL 2 -->
    <div class="" style=""  role="region" >
      <form id="triplesFrom" action="$thisPage" method="post">
  <div  id="textholder" class="textareadiv form-group flex-grow-1 d-flex flex-column">
    <textarea class="form-control flex-grow-1 rounded-0 detectTab" id="triplesTxt" name="triplesTxt"  style="overflow-y:scroll;" aria-label="Textarea for triples" rows="10">$triplesTxt</textarea>
    <div class="tbtns" style="">
        <button title="Refresh Model" class="btn btn-default textbtn" id="refreshM" type="submit"  aria-label="Refresh Model"><img aria-label="Refresh Model"  alt="Refresh Model" src="graphics/view-refresh.png" width="$bw" /></button>
        <button title="Clear Text" class="btn btn-default textbtn" id="clear" type="button"  aria-label="Clear Textarea"><img aria-label="Clear Text" alt="Clear Text" src="graphics/clear-text.png" width="$bw" /></button>
        <button title="Help" class="btn btn-default textbtn" id="help" type="button" data-bs-toggle="modal" data-bs-target="#helpModalCenter" aria-label="Open Help Modal"><img alt="Help" aria-label="Help" src="graphics/help.png" width="$bw" /></button>
        <button title="Toggle Text Fullscreen" class="btn btn-default textbtn" id="tfs" type="button"  aria-label="Toggle Textarea Full-screen" onclick="togglefullscreen('tfs', 'textholder')"><img alt="Toggle Fullscreen" aria-label="Toggle Fullscreen" src="graphics/view-fullscreen.png" width="$bw" /></button>
    </div>
  </div>
      </form>
    </div><!-- CLOSE LEVEL 2 -->
    <!-- LEVEL 3 -->
    <div  role="main" aria-label="Holder for the actual flow diagram model"  id="holder" class="flex-grow-1 moddiv">
  <div class="tbtns" style="">
    <div class="form-check form-switch">
			<input title="Toggle Pan & Zoom function" class="form-check-input" type="checkbox" role="switch" id="zoom-toggle" style="margin-right:0.5em; margin-bottom:2px; margin-top:8px; width:3em; height:1.5em;" onclick="modelZoom()">  
			<button title="Toggle Model Fullscreen" class="btn btn-default nav-button textbtn" id="fs"  aria-label="Toggle Model Full-screen"  style="top:0px;left:0px;" onclick="togglefullscreen('fs', 'holder')"><img   alt="Toggle Fullscreen"  aria-label="Toggle Fullscreen" src="graphics/view-fullscreen.png" width="$bw" /></button>
		</div>
</div>
      
  <!-- <div style="overflow: hidden; height: 100%;" tabindex=0> -->
  <div id="modelDiv" style="height:100%" class="mermaid">$mermaid</div>
  <!-- </div> -->
    </div><!-- CLOSE LEVEL 3 -->
  </div><!-- CLOSE FLEX DIV -->
$modal
</div><!-- CLOSE PAGE -->
      
  <script src="$jslib/jquery@$vs[1]/dist/jquery.min.js"></script>  
  <script src="$jslib/tether@$vs[4]/dist/js/tether.min.js"></script>
  <script src="$jslib/bootstrap@$vs[2]/dist/js/bootstrap.bundle.min.js"></script>
  <!-- <script src="$jslib/mermaid@$vs[3]/dist/mermaid.min.js"></script> -->
  <script type="module">
  import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';    
    let config = {
    maxTextSize: 900000,
    startOnLoad:true, 
    securityLevel: "loose",
    logLevel: 4,
    flowchart: { curve: 'basis', useMaxWidth: false, htmlLabels: true },
    mermaid: {
      callback:function(id) {modelZoom ()}
      }}
      
  mermaid.initialize(config);

</script>
  <script src="$jslib/pako@$vs[5]/dist/pako.min.js"></script>
  <script src="$jslib/js-base64@$vs[6]/base64.min.js"></script>
  <script src="./js/svg-pan-zoom.js" crossorigin="anonymous"></script> 
  <script src="./js/local.js"></script>
  <script>
 
  let code = JSON.stringify($json_code);
  let pcode = '$pako';
    
  </script>  
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
    "Information" => 'This is an interactive live modelling system which can automatically convert simple <b>tab</b> separated triples or JSON-LD (experimental) into graphical models and flow diagrams using the <a href="https://mermaid-js.github.io/">Mermaid Javascript library</a>. It has been designed to be very simple to use. The tab separated triples can be typed directly into the web-page, but users can also work and prepare data in three (or four columns if applying formatting) of a online spreadsheet and then just copy the relevant columns and paste them directly into the data entry text box.<br/><br/>In general the tool makes use of a simple set of predefined formats for the flow diagrams, taken from the Mermaid library, but a <a href="?example=example_formats">series of additional predefined formats</a> have also be provided and can be defined as a fourth "triple".<br/><br/>The <a href="./">default landing page</a> presents an example set or data, and the generated model demonstrates the functionality provided. As a new user it is recommended that you try editing this data to see how the diagrams are built. Additional examples are also available via the <b>Examples</b> menu option in the upper right.<br/><br/> The system has also be defined to allow models to be shared via automatically generate, and often quite long, URLs. This can be accessed via the <b>Links</b> menu option, as the <b>Bookmark Link</b>. A static image version of any given model can be saved by following the <b>Download Image</b> option and using the tools provide by the <a href="https://mermaid.ink/">Mermaid Ink</a> system. It is also possible to further edit a model using the full options of the Mermaid library using the <a href="https://mermaid-js.github.io/mermaid-live-editor">Mermaid Live Editor</a>, via the <b>Mermaid Editor</b> link.
    <br/><br/>
    <h5>Acknowledgements:</h5>
This tool was originally developed within the National Gallery, but its continue development and public presentation has also been supported by:
<br/><br/>
    <h6></a>The H2020 <a href="https://www.iperionhs.eu/" rel="nofollow">IPERION-HS</a> project</h6>
<p dir="auto"><a href="https://www.iperionhs.eu/" rel="nofollow"><img height="42px" src="./graphics/IPERION-HS%20Logo.png" alt="IPERION-HS" style="max-width: 100%;"></a>&nbsp;&nbsp;
<a href="https://www.iperionhs.eu/" rel="nofollow"><img height="32px" src="./graphics/iperionhs-eu-tag2.png" alt="IPERION-HS" style="max-width: 100%;"></a></p>
<br/>
<h6>The H2020 <a href="https://sshopencloud.eu/" rel="nofollow">SSHOC</a> project</h6>
<p><a href="https://sshopencloud.eu/" rel="nofollow"><img height="48px" src="./graphics/sshoc-logo.png" alt="SSHOC" style="max-width: 100%;"></a>&nbsp;&nbsp;
<a href="https://sshopencloud.eu/" rel="nofollow"><img height="32px" src="./graphics/sshoc-eu-tag2.png" alt="SSHOC" style="max-width: 100%;"></a></p>
<br/>
<h6>The AHRC Funded <a href="https://linked.art/" rel="nofollow">Linked.Art</a> project</h6>
<p><a href="https://ahrc.ukri.org/" rel="nofollow"><img height="48px" src="./graphics/UKRI_AHR_Council-Logo_Horiz-RGB.png" alt="Linked.Art" style="max-width: 100%;"></a></p>',
    //"Blank Nodes" => 'Details to be added',
    //"Formatting" => 'Details to be added',
    //"Aliases" => 'Details to be added'
    );
  
  $tabHeaders = false;
  $tabContents = false;

  $no = 1;
  $active = "active";
  foreach ($tabs as $k => $ht)
    {
    $dno = sprintf('%02d', $no);

    $tabHeaders .= "  
      <li class=\"nav-item\">
        <a href=\"#tab$dno\" class=\"nav-link $active\" data-bs-toggle=\"tab\">$k</a>
      </li>";
    
    $tabContents .= "  
      <div class=\"tab-pane fade show $active\" id=\"tab$dno\">
        <h5 class=\"text-center mb-4 mt-0 pt-4\">$k</h5>
        <div class=\"m-4\">$ht</div>
      </div>";
    
    $active = "";  
    $no++;
    }
  
  ob_start();
  echo <<<END
  <!-- Modal-->
  <div id="helpModalCenter" tabindex="-1" role="dialog" aria-label="Help Modal" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        
        <ul class="nav nav-tabs" id="myTab">
          $tabHeaders
        </ul>
        <div class="tab-content">
          $tabContents
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


function getCleanTriples($triplesTxt)
  {	
  $lastLine = 0;
  $cleanData = array();
  
  $data = explode("\n", $triplesTxt);
  
  foreach ($data as $k => $line) 
    {
    if (preg_match("/^.+\t.+\t.+$/", $line, $m))
      {$trip = explode ("\t", $line);}
    else if (preg_match("/^.+[,].+[,].+$/", $line, $m))
      {$trip = explode (",", $line);}
    else
      {$trip = array($line);}
      
    $trip = array_map('trim', $trip);

    // Starting things with @ can upset mermaid
    foreach ($trip as $tk => $tv)
      {
      if (preg_match("/^[\@](.+$)/", $tv, $m))
        {$tv = $m[1];}
        
      $trip[$tk] = parseEntities($tv);
      }
    
    //only consider the first 4 values - removes spaces coming from spreadsheets
    $trip = array_slice($trip, 0, 4);
    
    // Considered as a data line
    if ($trip[0])
      {$lastLine = $k;}
        
    // Allow gaps of up to two lines between blocks of triples and remove others.
    if ($k <= $lastLine + 2)
      {$cleanData[] = implode("\t", $trip);}
    }
    
  return ($cleanData);
  }

function getRaw($data)
  {
  global $orientation, $config, $fixlinks, $diagram, $subGraphs, $things,$subGraphCount;

  $au = $config["unique"]["regex"];
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
  $output[$tag]["objects"] = array();
  
  //pair rdf:type and crm:p2_has_type "objects"
  $typeObjects = array();

  foreach ($data as $k => $line) 
    {
    if (preg_match("/^.+\t.+\t.+$/", $line, $m))
      {$trip = explode ("\t", $line);}
    else if (preg_match("/^.+[,].+[,].+$/", $line, $m))
      {$trip = explode (",", $line);}
    else
      {$trip = array($line);}
      
    $trip = array_map('trim', $trip);

    // Starting things with @ can upset mermaid
    foreach ($trip as $tk => $tv)
      {if (preg_match("/^[\@](.+$)/", $tv, $m))
        {$trip[$tk] = $m[1];}}
  
    $trip["bn"] = false; //used to flag new blank nodes and possibly other formatting controls
    $trip["type"] = false; //used to flag new blank nodes and possibly other formatting controls
      
    // Increment triple number
    $tn++;
  
    if(preg_match("/^[\/][\/][ ]Model[:][\s]*([a-zA-Z0-9 ]+)[\s]*[\/][\/](.+)$/", $line, $m))
      {$output[$tag]["comment"] = $m[2];}
    else if((preg_match("/^[\/][\/][ ]*[gG]raph[ ]*([LT][BR])(.*)$/", $line, $m)) or
      (preg_match("/^[\/][\/][ ]*[gG]raph[ ]*([LT][BR])(.*)$/", $trip[0], $m)))
      {$orientation = $m[1];	
       $diagram = "graph";
       if (strtolower(trim($m[2])) == "fix") {$fixlinks = true;}
       $trip = array($line);}
    else if((preg_match("/^[\/][\/][ ]*[fF]lowchart[ ]*([LT][BR])(.*)$/", $line, $m)) or
      (preg_match("/^[\/][\/][ ]*[fF]lowchart[ ]*([LT][BR])(.*)$/", $trip[0], $m)))
      {$orientation = $m[1];
       $diagram = "flowchart";
       if (strtolower(trim($m[2])) == "fix") {$fixlinks = true;}
       $trip = array($line);}
    else if(preg_match("/^[\/][\/][ ]*[sS][uU][bB][gG][Rr][Aa][Pp][Hh[ ]*(.*)$/", $line, $m))
      {
      $subGraphCount++;
      $sgdts = array();
      $sg = trim ($m[1]);
      
      if (preg_match("/^[\"][\/][\/](.+)[\"]$/", $sg, $sm))
        {$sgdts["id"] = str_replace(' ', "", $sm[1]);
         $sgdts["lab"] = "[\"&nbsp;\"]";}
      else if (preg_match("/^[\/][\/](.+)$/", $sg, $sm))
        {$sgdts["id"] = str_replace(' ', "", $sm[1]);
         $sgdts["lab"] = "[\"&nbsp;\"]";}
      else if (!$sg)
	{$sgdts["id"] = "sgID-".$subGraphCount;
         $sgdts["lab"] = "[\"&nbsp;\"]";
	 $sg = "//".$sgdts["id"];}
      else
        {$sgdts["id"] = str_replace(' ', "", $sg);          
         $sgdts["lab"] = "[\"$sg\"]";}
         
      $subGraphs[$sg] = $sgdts;
      $things[$sg] = $sgdts["id"];
      $trip = array("subgraph", $sg, "");
      }
    else if(preg_match("/^[\/][\/][ ]*[eE][nN][dD][ ]*(.*)$/", $line, $m))
  {$trip = array("end", $m[1], "");}
      // ignore lines that are commented out
      else if(preg_match("/^[\/#][\/#].*$/", $line, $m)) 
  {$trip = array($line);}

      // Ignore notes, empty lines or commented lines
      if (isset($trip[2]))
  {
  if (in_array(strtolower($trip[0]), array("_blank node", "_bn")))
    {$bnd = true;
     $trip["bn"] = true;}
  else
    {$bnd = false;}

  $typeCheck = preg_replace('/[. ]/', "_", strtolower($trip[1]));
  //echo "<!-- $typeCheck -->\n";
  // Defining a thing as have type "Type" is a special case so the "Type" is left as a literal by default
  if (in_array ($typeCheck, array(
    "crm:p2_has_type", "has_type", "type", "rdf:type", "classified_as")) and strtolower($trip[2]) != "type")
    {$pt = true;
     $trip["type"] = true;}
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
  if (in_array(strtolower($trip[2]), array("_blank node", "_bn")))
    {$trip[2] = $trip[2]."-N".$bn;
     $bnew=false;}
  else if (preg_match("/^(_[bB][a-z]*[ ]*[Nn][a-z]*)[-]([0-9]+)$/", $trip[2], $m))
    {$trip[2] = $m[1]."-N".($bn-$m[2]);}

  // Number the predicates so they are all unique
  // NOT REQUIRED                   
  //$trip[1] = $trip[1]."-N".$tn;

  // Ensure that all refs to listed unique classes are unique so the diagram
  // does not Overlap too much - only parsing "subjects"
  foreach ($au  as $rxk => $rxv)
    {
    if(preg_match("/^$rxv$/", $trip[2], $m))
      {
      $check = strtolower($trip[0]."-".$trip[2]);
      if (isset($typeObjects[$check]))
        {$trip[2] = $typeObjects[$check];}//$trip[2]."-". $tn;}
      else
        {$typeObjects[$check] = $trip[2]."-". $tn;
         $trip[2] = $trip[2]."-". $tn;}
      break 1;
      }
    }

  // list unique "objects"
  if (!in_array($trip[0], $output[$tag]["objects"]))
    {$output[$tag]["objects"][] = $trip[0];}
    
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
 
function checkForSubgraphId ($id)
  {
  global $subGraphs;
  
  if (isset($subGraphs[$id]))
    {return ($subGraphs[$id]["id"]);}
  else
    {return ($id);}
  }
 
function Mermaid_displayLabel ($str)
  {
  global $config;  
  
  //We do not want to display "/r" at all.
  $str = str_replace('\r', "", $str);
  // Format the displayed text, either wrapping or removing numbers
  // used to indicate separate instances of the same text/name
  if (count_chars($str) > 60)
    {$out = wordwrap($str, 60, "<br/>", true);}
  else
    {$out = $str;}
  
  $au = $config["unique"]["regex"];
        
  // If entities have been numbered to force them to be unique
  // hide the number from being displayed
  foreach ($au  as $pk => $pr)
    {
    if(preg_match("/^(.+)[#][-][0-9]+$/", $out, $m))
      {$out = $m[1];
        break 1;}
    else if(preg_match("/^(${pr})[-][0-9]+$/", $out, $m))
      {$out = $m[1];
       break 1;}
    }
      
  return($out);
  }
  
  
function Mermaid_formatData ($selected)
  {
  global $orientation, $config, $allClasses, $usedClasses, $fixlinks, $diagram, $subGraphs, $thisPage;

  $defs = "";
  $things = array();
  $no = 0;
  $objs = array();
  $au = $config["unique"]["regex"];
  $sgIDs = array();
  
  if ($diagram == "flowchart")
    {foreach ($subGraphs as $n => $a)
      {$things[$n] = $a["id"];}}

  // loop through to format display texts and out put mermaid code
  foreach ($selected["triples"] as $k => $t) 
    {
    $ot = $t;
    $t[1] = check_string($t[1]);
    $t[2] = str_replace('"', "#34;", $t[2]);
    $t[2] = str_replace('?', "#63;", $t[2]);
    
    // Updated to allow formats classes to be added to subgraphs - 05/07/22 JPadfield
    // Commenting out a subgraph name with "//" will hide the subgraph label. - 17/08/22 JPadfield
    if (in_array($t[0], array("subgraph")))
      {
      $sgdts = $subGraphs[trim($t[1], "\"")];
      $sgIDs[] = $sgdts["id"];  
      $defs .= "\n$t[0] $sgdts[id] $sgdts[lab]\n";      
      }
    else if (in_array($t[0], array("end")))
      {
      $defs .= "\n$t[0]\n";
      $sgID = array_pop($sgIDs);
      
      if ($t[1]) 
        {    
        if(isset($allClasses[$t[1]]))
          {$usedClasses[$t[1]] = $allClasses[$t[1]];}
        else //added the option to used custom dashes on the border line initially for subgraphs but also added for nodes - J Padfield 22/6/23
          {
					if (preg_match("/^(.+)[-]([0-9]+)[-]([0-9]+)$/", $t[1], $cm))
						{
						if(isset($allClasses[$cm[1]]))
							{
							$tc = rtrim(trim($allClasses[$cm[1]]), ';');
							$tc = preg_replace("/classDef $cm[1]/", "classDef $t[1]", $tc);
							$tc = $tc.",stroke-dasharray:$cm[2] $cm[3];\n";
							$allClasses[$t[1]] = $tc;
							$usedClasses[$t[1]] = $allClasses[$t[1]];
							}								
						}	
					}
        $defs .= "class $sgID $t[1]\n";
        }
      }
    else if ($t[1] == "tooltip")
      {
      // Some of this section is a duplication of the steps below so it might be good to look at moving this tooltips section down later
    
      // Allow the user to force the formatting classes used for the
      // object and subject
      $fcs = array(false, false);
   
      if(isset($t[3]))
        {
        $fcs = explode ("|", $t[3]);
        if(!isset($fcs[1]))
          {$fcs = explode ("@@", $t[3]);}
            
        if(!isset($fcs[1]))
          {$fcs[1] = false;}
        }
      
      if (!$fcs[1] and isset($t[1]) and $t[1] == "has note")
        {$fcs = array(false, "note");}
        
      if (!isset($things[$t[0]]))
        {
        $things[$t[0]] = "O".$no;
      
        // Default objects to object class
        if (!$fcs[0] and !preg_match ("/^[a-zA-Z]+[:].+$/", $t[0], $m))
          {
          if ($t["bn"] ) {$fcs[0] = "object_bn";}
          else {$fcs[0] = "object";}
          }
        
        $defs .= Mermaid_defThing($t[0], $no, $fcs[0]);
        $no++;
        }
      
      $use_0 = "[\"".Mermaid_displayLabel ($t[0])."\"]";
      // Adding a display label after the click does not work so it is
      // added to a single node before hand      
      $defs .=  $things[$t[0]].$use_0."\n";
      $defs .= "click ".$things[$t[0]]." function \"$t[2]\"; \n";
      }
    else
      {
      // Catch URL with an ALT text suffix
      $t0extraLink = false;
      $t2extraLink = false;
        
      if (preg_match("/^([^|]+)[|](http.+)$/", $t[0], $t0m) or
        preg_match("/^([^|]+)[|](\?.+)$/", $t[0], $t0m))
        {$t0extraLink = $t0m[2];
         $t[0] = $t0m[1];}        
    
      if (preg_match("/^([^|]+)[|](http.+)$/", $t[2], $t2m) or
        preg_match("/^([^|]+)[|](\?.+)$/", $t[2], $t2m))
        {$t2extraLink = $t2m[2];
         $t[2] = $t2m[1];}
      
      $use_0 = Mermaid_displayLabel ($t[0]);
      $use_2 = Mermaid_displayLabel ($t[2]);
        
      /*// Format the displayed text, either wrapping or removing numbers
      // used to indicate separate instances of the same text/name
      if (count_chars($t[2]) > 60)
        {$use = wordwrap($t[2], 60, "<br/>", true);}
      else
        {$use = $t[2];}
        
      // Consider allowing wrapping for the subjects as well.
      if (count_chars($t[0]) > 60)
        {$use0 = wordwrap($t[0], 60, "<br/>", true);}
      else
        {$use0 = $t[0];}
      
      // If entities have been numbered to force them to be unique
      // hide the number from being displayed
      foreach ($au  as $pk => $pr)
        {
        if(preg_match("/^(.+)[#][-][0-9]+$/", $use0, $m))
          {$use0 = $m[1];
           break 1;}
        else if(preg_match("/^(${pr})[-][0-9]+$/", $use0, $m))
          {$use0 = $m[1];
           break 1;}
        }
      ////////////////////////////////////////////////////////////  
        
      // If entities have been numbered to force them to be unique
      // hide the number from being displayed
      foreach ($au  as $pk => $pr)
        {
        if(preg_match("/^(.+)[#][-][0-9]+$/", $use, $m))
          {$use = $m[1];
           break 1;}
        else if(preg_match("/^(${pr})[-][0-9]+$/", $use, $m))
          {$use = $m[1];
           break 1;}
        }*/

      // Allow the user to force the formatting classes used for the
      // object and subject
        $fcs = array(false, false);
   
      if(isset($t[3]))
        {
        $fcs = explode ("|", $t[3]);
      
        if(!isset($fcs[1]))
          {$fcs = explode ("@@", $t[3]);}
            
        if(!isset($fcs[1]))
          {$fcs[1] = false;}
        }
      
      if (!$fcs[1] and isset($t[1]) and $t[1] == "has note")
        {$fcs = array(false, "note");}
      else if (!$fcs[1] and $t2extraLink)
        {$fcs[1] = "url";}
                
      if (!isset($things[$t[0]]))
        {
        $things[$t[0]] = "O".$no;
      
        // Default objects to object class
        if (!$fcs[0] and !preg_match ("/^[a-zA-Z]+[:].+$/", $t[0], $m))
          {
          if ($t["bn"] ) {$fcs[0] = "object_bn";}
          else {$fcs[0] = "object";}
          }
        
        $defs .= Mermaid_defThing($t[0], $no, $fcs[0]);
        $no++; 
        }
    
      //  NEED TO REVISIT THE AUTOMATIC ASSIGNMENT OF object class
      // NEED NEW RULES
      if (!isset($things[$t[2]]))
        {
        if (preg_match ("/^([a-zA-Z]+)([:].+)$/", $t[2], $m))
          {
          $prf = strtolower($m[1]);
          //$t[2] = $prf.$m[2];
          }
        else
          {$prf = false;}
  
        $things[$t[2]] = "O".$no;

        // Default objects to object class
        if (!$fcs[1] and !$prf and isset($objs[$t[2]]))
          {$fcs[1] = "object";}
        else if (!$fcs[1] and  $t["type"] and !in_array(strtolower($prf), array_keys($config["prefix"])))
          {$fcs[1] = "type";}
        $defs .= Mermaid_defThing($t[2], $no, $fcs[1]);
        $no++;
        }  
    
      // If an ALT suffix was supplied for a link, add ref to the link back in with a tool tip.
      if ($t0extraLink)
        {$pp = pathinfo($t0extraLink);
         $tt = "Link to: $pp[dirname] ...";
         $defs .= "click ".$things[$t[0]]." \"$t0extraLink\" \"$tt\"; \n";}
      if ($t2extraLink)
        {$pp = pathinfo($t2extraLink);
         $tt = "Link to: $pp[dirname] ...";
         $defs .= "click ".$things[$t[2]]." \"$t2extraLink\" \"$tt\"; \n";}
 
 // need to check for an image and if so update $use
 // O6[&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<img src='https://research.ng-london.org.uk/iiif/pics/tmp/raphael_pyr/N-1171/08_Images_of_Frames/raphael%20capitals%20right%20and%20left-PYR.tif/full/,125/0/default.jpg'/>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp]  
      if (preg_match ("/^IGNOREJUSTNOWASITDOESNOTSEEMTOWORKhttp.+[jpegpn]+$/", $t[2], $m))
        {$tmp = check_string("<img src='$t[2]'/>");
         $use_2 = "[&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp$tmp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp]";}
      else
        {$use_0 = "[\"".$use_0."\"]";
         $use_2 = "[\"".$use_2."\"]";}
    
      if ($fixlinks)
        {$lformat = " ---->|$t[1]|";}
      else
        {$lformat = " -- ".$t[1]. " -->";}
      
      //$defs .= $things[$t[0]]." -- ".$t[1]. " -->".$things[$t[2]].
      $defs .= $things[$t[0]].$use_0.$lformat.$things[$t[2]].
      $use_2."\n";    
      }
  
    }//exit;

  $defs = "$diagram $orientation\n".
    implode("", $usedClasses).
    "\n$defs;";
  
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
        {$pp = pathinfo($a["url"]);
         $tt = "Link to: $pp[dirname] ...";
         $click = "click ".$code." \"".$a["url"]."$m[1]\" \"$tt\"; \n";}        
      else if(preg_match("/^http.+$/", $var, $m))
        {$click = "click ".$code." \"".$var."\"\n";}
      break;
      }    
    }
  if ($fc) {$cls = $fc;}

  if(isset($allClasses[$cls]))
    {$usedClasses[$cls] = $allClasses[$cls];}
  else //added the option to used custom dashes on the border line initially for subgraphs but also added for nodes - J Padfield 22/6/23
    {
		if (preg_match("/^(.+)[-]([0-9]+)[-]([0-9]+)$/", $cls, $cm))
			{
			if(isset($allClasses[$cm[1]]))
				{
				$tc = rtrim(trim($allClasses[$cm[1]]), ';');
				$tc = preg_replace("/classDef $cm[1]/", "classDef $cls", $tc);
				$tc = $tc.",stroke-dasharray:$cm[2] $cm[3];\n";
				$allClasses[$cls] = $tc;
				$usedClasses[$cls] = $allClasses[$cls];
				}								
			}	
		}
         
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
    $triplesTxt = "//flowchart TB fix\n";
    //prg(0, $json);
    //var_dump($json);
    if (isset($json[0]["@context"]))
      {$json = $json[0];
       $triplesTxt .= "This Model\thas context\t".$json["@context"]."\n";
       unset($json["@context"]);}
    else if (isset($json["@context"]))
      {$triplesTxt .= "This Model\thas context\t".$json["@context"]."\n";
       unset($json["@context"]);}
    else
      {}
    
    $triplesTxt .= laj2trips ($json);
    //prg(1, $triplesTxt);
    
    //debugJsonConversaion ($data, $json, $triplesTxt);
    }
  else
    {$triplesTxt = $data;}    
  
  return ($triplesTxt);
  }

function isArrayAssociative($array) {
    return array_keys($array) !== range(0, count($array) - 1);
}

$bn_number = "0";
$la_formats = array();
function pickLaFormat ($s, $p, $o)
  {
  global $la_formats;
  
  $oformat = "";
  $sformat = "";
  
  if ($p == "type")
    {$oformat = "classstyle";}
  elseif (in_array($p, array("_label", "content")))
    {$oformat = "literal";}
  elseif (in_array($p, array("part", "equivalent")))
    {
    if (isset($la_formats[$s]))
      {$oformat = $la_formats[$s];}      
    }
  elseif (in_array($p, array("member_of")))
    {$oformat = "actor2";}
  elseif (in_array($p, array("born", "died", "formed_by", "dissolved_by", "carried_out")))
    {$oformat = "event2";}
  elseif (in_array($p, array("timespan")))
    {$oformat = "timespan";}
  elseif (in_array($p, array("member_of", "representation", "referred_to_by", "subject_of")))
    {$oformat = "infoobj";}
  elseif (in_array($p, array("took_place_at")))
    {$oformat = "place2";}
  elseif (in_array($p, array("classified_as")))
    {$oformat = "type2";}
  elseif (in_array($p, array("identified_by", "contact_point")))
    {$oformat = "name2";}
    
  if (in_array($o, array("Person", "Group", "Actor")))
    {$sformat = "actor2";}
    
  if ($oformat)
    {$la_formats[$o] = $oformat;}
  if ($sformat)
    {$la_formats[$s] = $sformat;}
      
  if ($oformat or $sformat)
    {$format = "\t$sformat|$oformat";}
  else
    {$format = "";}
  
  return ($format);  
  }
  
// Seem to be getting duplicates, might be good to drop all of the triples into an array
// remove the duplicates and then order them, add formats and then output
function laj2trips ($arr, $pSub=false, $pPred=false)
  {
  global $bn_number;
  
  if (!is_array($arr))
    {$arr = array("id" => $arr);}
    
  $out = "";
  
  if (isset($arr["id"]))
    {$sub = $arr["id"];
     unset($arr["id"]);}
  else
    {if ($bn_number)
      {$sub = "_#-".$bn_number;}
     else
      {$sub = "_#-0";}
     $bn_number++;}
  
  if ($pSub and $pPred)
    {
    //$out .= "//Link triple\n";
    $pSub = parseEntities($pSub);
    $sub = parseEntities($sub);
    $pPred = parseEntities($pPred);
    
    $fr = pickLaFormat ($pSub,$pPred,$sub);
    $out .= "$pSub\t$pPred\t$sub$fr\n";}
    
  foreach ($arr as $k => $v)
    {
    if (is_array($v))
      {
      if(isset($v["id"]))
        {$out .= laj2trips($v, $sub, $k);}
      elseif (!isArrayAssociative($v))
        {//$out .= "//Simple Array\n";
         foreach ($v as $n => $a)
          {
          if (is_array($a)) {$out .= laj2trips($a, $sub, $k);}
          else {
            $a = parseEntities($a);
            $sub = parseEntities($sub);
            $k = parseEntities($k);
            $fr = pickLaFormat ($sub,$k,$a);
            $out .= "$sub\t$k\t$a$fr\n";
            }          
          }
        }      
      else
        {//$out .= "//Associative Array\n";
         $out .= laj2trips($v, $sub, $k);
         //foreach ($v as $n => $a)
         // {$out .= laj2trips($a, $sub, $k);}
         }
      }
    else
      {
      //$out .= "//Simple Triple\n";
      $v = parseEntities($v);
      //needed to cope with complex newlines
      if (in_array($k, array("_label", "content")))
        {$v = json_encode($v);}
      $sub = parseEntities($sub);
      $k = parseEntities($k);
      $fr = pickLaFormat ($sub,$k,$v);
      $out .= "$sub\t$k\t$v$fr\n";
      }
    }

  return($out);
  }

function parseEntities($name)
  {

  if (preg_match("/^http[s]*[:][\/]+vocab[.]getty[.]edu[\/]aat[\/]([0-9]+)$/", $name, $m))
    {$out = "aat:$m[1]|$name";}
  else if (preg_match("/^http[s]*[:][\/]+vocab[.]getty[.]edu[\/]ulan[\/]([0-9]+)$/", $name, $m))
    {$out = "ulan:$m[1]|$name";}
  else if (preg_match("/^http[s]*[:][\/]+data[.]ng[-]london[.]org[.]uk[\/]([0-9A-Z-]+)$/", $name, $m))
    {$out = "ng:$m[1]|$name";}
  else if (preg_match("/^http[s]*[:][\/]+data[.]ng[.]ac[.]uk[\/]([0-9A-Z-]+)$/", $name, $m))
    {$out = "ng:$m[1]|$name";}
  else if (preg_match("/^http[s]*[:][\/]+linked[.]art[\/]example[\/]([a-z]+[\/][0-9]+)$/", $name, $m))
    {$out = "lae:$m[1]|$name";}
  else
    {$out = $name;}

  return($out);
  }
  
function getsslfile ($uri, $decode=true)
	{
	$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,),);  

	$response = @file_get_contents($uri, false, stream_context_create($arrContextOptions));
	
	if ($decode)
		{return (json_decode($response, true));}
	else
		{return ($response);}
	}
  
function getRemotePage ($uri)
	{
  // Initialize a connection with cURL (ch = cURL handle, or "channel")
$ch = curl_init();

// Set the URL
curl_setopt($ch, CURLOPT_URL, $uri);

// Set the HTTP method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

// Return the response instead of printing it out
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Send the request and store the result in $response
$response = curl_exec($ch);

//echo 'HTTP Status Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
//echo 'Response Body: ' . $response . PHP_EOL;

// Close cURL resource to free up system resources
curl_close($ch);  
    
  return ($response);
  }
  
function getRemoteURL ($url)
  {
  $bits = explode("/", $url);
  $b1 = array_shift($bits);
  
  foreach ($bits as $k => $v)
    {$bits[$k] = rawurlencode($v);}
    
  $url = $b1."/".implode("/", $bits);
  
  $fc = file_get_contents($url);  
  
  return ($fc);
  }
  
function getRemoteJsonDetails ($uri, $format=false, $decode=false)
  {
  
  
  if ($format) {$uri = $uri.".".$format;}
   $fc = file_get_contents($uri);
   if ($decode)
    {$output = json_decode($fc, true);}
   else
    {$output = $fc;}
   return ($output);}
   
function check_string($my_string)
  {
  // Excluded: ";", '#59;' - "#", '#35;' - "@", '#64;' - ":", '#58;' - "/", '#47;'
  $chars = array('!', '*', '"', "'", "(", ")", "&", "=", "+", 
    "$", ",", "?", "%", "[", "]", "\\");
  $codes = array('#33;', '#42;', '#34;', '#39;', '#40;', '#41;', '#38;', '#61;',
    '#43;', '#36;', '#44;', '#63;', '#37;', '#91;', '#93;', 
    '#92;');
  
  $my_string = trim ($my_string);
  
  if (preg_match('/^.*[^a-zA-Z0-9 |_#-].*$/', $my_string))
    {$my_string = trim ($my_string, '"');
     $my_string = '"'.str_replace($chars, $codes, $my_string).'"';}
    
  return ($my_string);
  }
  
function getModelImage ($code)
  {  
  $live_img_link = 'https://mermaid.ink/img/pako:'.$code.'?type=png';
  $im = imagecreatefrompng($live_img_link);
  imageAlphaBlending($im, true);
  imageSaveAlpha($im, true);
  
  header('Content-Type: image/png');
  imagepng($im);
  imagedestroy($im);
  exit;  
  }

function getTriplesDetails ($id, $decode=false)
	{
  global $thisPage;
  
  $uri = $thisPage."infl.php?data=".$id;
  $fc = getRemotePage ($uri);
  //exit;
  //prg(0, $uri);
  //$fc = file_get_contents($uri);
  //echo "######################################################<pre>";
  // prg(0, $fc); 
  //echo "</pre>######################################################";
	//$fc = file_get_contents($id."/export/json");
	//$fc = explode("\n", $fc);
//  prg(1, $fc);
	$out = "";
	$echo = false;
	 
	foreach ($fc as $k => $line)
		{
    prg(0, $line);
		if ($line == "<pre class=\"triples\">")
			{$line = "{";
			 $echo = true;}
		else if ($line == "</pre>")
			{$echo = false;}
		
		if ($echo)
			{$out .= $line."\n";}
		}
		
	$out = htmlspecialchars_decode($out);
	 
	if ($decode)
		{$output = json_decode($out, true);}
	else
		{$output = $out;}
		
	return ($output);
	}
?>
