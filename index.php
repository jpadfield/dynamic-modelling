<?php

$versions = array(
	"jquery" => "3.6.0",
	"bootstrap" => "5.1.3",
	//"mermaid" => "8.14.0"
	"mermaid" => "9.1.3"
	);
  
if (isset($_GET["debug"])) {}
	
if (isset($_SERVER["SCRIPT_URL"]))
	{$thisPage = $_SERVER["SCRIPT_URL"];}
else
	{$thisPage = "./";}

if (isset($_SERVER["HTTP_X_FORWARDED_HOST"]) and  $_SERVER["HTTP_X_FORWARDED_HOST"] == "round4.ng-london.org.uk")
	{$thisPage = "/ex".$thisPage;}
	
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

// Thumbnail display might be possible with
//    
// O4 -- "crm:P48_has_preferred_identifier" -->O6[&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<img src='https://research.ng-london.org.uk/iiif/pics/tmp/raphael_pyr/N-1171/08_Images_of_Frames/raphael%20capitals%20right%20and%20left-PYR.tif/full/,125/0/default.jpg'/>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp]

// The text "&nbsp"s are required to get the box to be bigger - it results in a slide like display.
    

$data = urlencode(base64_encode(gzcompress($triplesTxt)));
$bookmark = $thisPage.'?data='.$data;

//prg(0, $triplesTxt);
$triples = getCleanTriples($triplesTxt);
//prg(1, $triples);
$cleanTriplesTxt = implode("\n", $triples);
//prg(0, $cleanTriplesTxt);
$raw = getRaw($triples);
//prg(0, $raw);
$mermaid = Mermaid_formatData ($raw["test"]);
//prg(0, $mermaid);
	
$html = buildPage ($cleanTriplesTxt, $mermaid);
echo $html;
exit;

////////////////////////////////////////////////////////////////////////

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
  global $live_edit_link, $bookmark;

  ob_start(); //style="margin-right: 8px; float:right; margin-bottom: 16px;" 
  echo <<<END
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenuLinks" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      Links
    </a>
  <div class="dropdown-menu  dropdown-menu-end" aria-labelledby="dropdownMenuLinks">
    <a class="dropdown-item" title="Mermaid Live Editor" href=" $live_edit_link" target="_blank">Get Image</a>
    <a class="dropdown-item" title="Bookmark Link" href=" $bookmark" target="_blank">Bookmark Link</a>
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
  global  $live_edit_link, $bookmark, $thisPage, $versions;

  $exms = buildExamplesDD ();
  $links = buildLinksDD ();
  $modal = buildModal ();

	$bw = "26px";
	
  ob_start();
  echo <<<END

<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta charset="utf-8">
  <title>Dynamic Simple Modelling</title>
  <link href="https://unpkg.com/bootstrap@${versions[bootstrap]}/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="local-dev.css" rel="stylesheet" type="text/css">
  <style></style>
  <!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-P2QQWTBKX7"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-P2QQWTBKX7');
</script>
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
	      <button title="Toggle Fullscreen" class="btn btn-default textbtn" id="tfs" type="button"  aria-label="Toggle Textarea Full-screen" onclick="togglefullscreen('tfs', 'textholder')"><img alt="Toggle Fullscreen" aria-label="Toggle Fullscreen" src="graphics/view-fullscreen.png" width="$bw" /></button>
	  </div>
	</div>
      </form>
    </div><!-- CLOSE LEVEL 2 -->
    <!-- LEVEL 3 -->
    <div  role="main" aria-label="Holder for the actual flow diagram model"  id="holder" class="flex-grow-1 moddiv">
	<div class="tbtns" style="">
	    <button class="btn btn-default nav-button textbtn" id="fs"  aria-label="Toggle Model Full-screen"  style="top:0px;left:0px;" onclick="togglefullscreen('fs', 'holder')"><img   alt="Toggle Fullscreen"  aria-label="Toggle Fullscreen" src="graphics/view-fullscreen.png" width="$bw" /></button></div>
	<!-- <div style="overflow: hidden; height: 100%;" tabindex=0> -->
	$mermaid
	<!-- </div> -->
    </div><!-- CLOSE LEVEL 3 -->
  </div><!-- CLOSE FLEX DIV -->
$modal
</div><!-- CLOSE PAGE -->
      
  <script src="https://unpkg.com/jquery@${versions[jquery]}/dist/jquery.min.js"></script>	<script src="https://unpkg.com/tether@1.4.7/dist/js/tether.min.js"></script>
  <script src="https://unpkg.com/bootstrap@${versions[bootstrap]}/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/mermaid@${versions[mermaid]}/dist/mermaid.min.js"></script>
  <script src="./svg-pan-zoom.js" crossorigin="anonymous"></script> 
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
    "Summary" => 'This is an interactive live modelling system which can automatically convert simple <b>tab</b> separated triples or JSON-LD into graphical models and flow diagrams using the <a href="https://mermaid-js.github.io/">Mermaid Javascript library</a>. It has been designed to be very simple to use. The tab separated triples can be typed directly into the web-page, but users can also work and prepare data in three (or four columns if applying formatting) of a online spreadsheet and then just copy the relevant columns and paste them directly into the data entry text box.<br/><br/>In general the tools makes use of a simple set of predefined formats for the flow diagrams, taken from the Mermaid library, but a <a href="?example=example_formats">series of additional predefined formats</a> have also be provided and can be defined as a fourth "triple".<br/><br/>The <a href="./">default landing page</a> presents and example set or data, and the generated model, this example demonstrate the functionality provided. As a new user it is recommended that you try editing this data to see how the diagrams are built. Additional examples are also available via the <b>Examples</b> menu option in the upper right.<br/><br/> The system has also be defined to allow models to be shared via automatically generate, and often quite long, URLs. This can be accessed via the <b>Links</b> menu option, as the <b>Bookmark Link</b>. It is also possible to generate a static image version of any given model by following the <b>Get Image</b> option and using the tools provide by the <a href="https://mermaid-js.github.io/mermaid-live-editor">Mermaid Live Editor</a>.
    <br/><br/>
    <h5>This specific project was supported by:</h5>
<br/>
		<h6>The H2020 <a href="https://sshopencloud.eu/" rel="nofollow">SSHOC</a> project</h6>
<p><a href="https://sshopencloud.eu/" rel="nofollow"><img height="48px" src="https://github.com/jpadfield/simple-modelling/raw/master/docs/graphics/sshoc-logo.png" alt="SSHOC" style="max-width: 100%;"></a>&nbsp;&nbsp;
<a href="https://sshopencloud.eu/" rel="nofollow"><img height="32px" src="https://github.com/jpadfield/simple-modelling/raw/master/docs/graphics/sshoc-eu-tag2.png" alt="SSHOC" style="max-width: 100%;"></a></p>
<br/>
<h6></a>The H2020 <a href="https://www.iperionhs.eu/" rel="nofollow">IPERION-HS</a> project</h6>
<p dir="auto"><a href="https://www.iperionhs.eu/" rel="nofollow"><img height="42px" src="https://github.com/jpadfield/simple-modelling/raw/master/docs/graphics/IPERION-HS%20Logo.png" alt="IPERION-HS" style="max-width: 100%;"></a>&nbsp;&nbsp;
<a href="https://www.iperionhs.eu/" rel="nofollow"><img height="32px" src="https://github.com/jpadfield/simple-modelling/raw/master/docs/graphics/iperionhs-eu-tag2.png" alt="IPERION-HS" style="max-width: 100%;"></a></p>
<br/>
<h6>The AHRC Funded <a href="https://linked.art/" rel="nofollow">Linked.Art</a> project</h6>
<p><a href="https://ahrc.ukri.org/" rel="nofollow"><img height="48px" src="https://github.com/jpadfield/simple-modelling/raw/master/docs/graphics/UKRI_AHR_Council-Logo_Horiz-RGB.png" alt="Linked.Art" style="max-width: 100%;"></a></p>',
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
  global $orientation, $config;

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
        $trip = array($line);}
      else if(preg_match("/^[\/][\/][ ]*[sS][uU][bB][gG][Rr][Aa][Pp][Hh[ ]*(.*)$/", $line, $m))
	{$trip = array("subgraph", $m[1], "");}
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
	  "crm:p2_has_type", "has_type", "type", "rdf:type")) and strtolower($trip[2]) != "type")
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
  
function Mermaid_formatData ($selected)
  {
  global $orientation, $live_edit_link, $config, $allClasses, $usedClasses;

  $defs = "";
  $things = array();
  $no = 0;
  $objs = array();
  $au = $config["unique"]["regex"];
  $sgIDs = array();

  // loop through to format display texts and out put mermaid code
  foreach ($selected["triples"] as $k => $t) 
    {
		$t[1] = check_string($t[1]);
    $t[2] = str_replace('"', "#34;", $t[2]);
    $t[2] = str_replace('?', "#63;", $t[2]);
    //$t[2] = check_string($t[2]);
    
    // Updated to allow formats classes to be added to subgraphs - 05/07/22 JPadfield
    if (in_array($t[0], array("subgraph")))
	{$tid = str_replace(' ', "", $t[1]);
	 $sgIDs[] = $tid;
	 $defs .= "\n$t[0] $tid [\"$t[1]\"]\n";
	 }
    else if (in_array($t[0], array("end")))
	{
	$defs .= "\n$t[0] $t[1]\n";
	$sgID = array_pop($sgIDs);
	if ($t[1]) 
	  {	  
	  if(isset($allClasses[$t[1]]))
	    {$usedClasses[$t[1]] = $allClasses[$t[1]];}
	  $defs .= "class $sgID $t[1]\n";
	  }
	}
    else
	{
    // Format the displayed text, either wrapping or removing numbers
    // used to indicate separate instances of the same text/name
    if (count_chars($t[2]) > 60)
      {$use = wordwrap($t[2], 60, "<br/>", true);}
    else
      {$use = $t[2];}

    // If entities have been numbered to force them to be unique
    // hide the number from being displayed
    foreach ($au  as $pk => $pr)
      {if(preg_match("/^(.+)[#][-][0-9]+$/", $use, $m))
        {$use = $m[1];
	  break 1;}
       else if(preg_match("/^(${pr})[-][0-9]+$/", $use, $m))
	{$use = $m[1];
	 break 1;}}

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

    //  NEED TO REVISIT THE AUTOMATIC ASSIGNMENT OF object class
    // NEED NEW RULES
    if (!isset($things[$t[2]]))
      {
      if (preg_match ("/^([a-zA-Z]+)([:].+)$/", $t[2], $m))
	{$prf = strtolower($m[1]);
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
       $no++;}		
 
 // need to check for an image and if so update $use
 // O6[&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<img src='https://research.ng-london.org.uk/iiif/pics/tmp/raphael_pyr/N-1171/08_Images_of_Frames/raphael%20capitals%20right%20and%20left-PYR.tif/full/,125/0/default.jpg'/>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp]  
    if (preg_match ("/^IGNOREJUSTNOWASITDOESNOTSEEMTOWORKhttp.+[jpegpn]+$/", $t[2], $m))
      {$tmp = check_string("<img src='$t[2]'/>");
       $use = "[&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp$tmp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp]";}
    else
      {$use = "[\"".$use."\"]";}
      
    $defs .= $things[$t[0]]." -- ".$t[1]. " -->".$things[$t[2]].
      $use."\n";		
	}
    }//exit;

  $defs = "graph $orientation\n".
    implode("", $usedClasses).
    "\n$defs;";
  
  //prg(1, $defs);
  $code = array(
    "code" => $defs,
    "mermaid" => array(
      "theme" => "default",
      "securityLevel" => "loose",
      "logLevel" => "warn",
      "flowchart" => array( 
	  "curve" => "basis",
    "htmlLabels" => true)
      ));
  $json = json_encode($code);
  $code = base64_encode($json);
  $live_edit_link = 'https://mermaid-js.github.io/mermaid-live-editor/#/edit/'.$code;
  $defs = "<div id=\"modelDiv\" style=\"height:100%\" class=\"mermaid\">".$defs."</div>";
	
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


// NEED TO CATCH IF $arr is not an array !!!!
function laj2trips ($arr, $pSub=false, $pPred=false)
  {
  //if (!is_array($arr))
  //  {prg(1, $arr);}
    
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
  else if (preg_match("/^http[s]*[:][\/]+data[.]ng[-]london[.]org[.]uk[\/]([0-9A-Z-]+)$/", $name, $m))
    {$out = "ng:$m[1]";}
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

?>
