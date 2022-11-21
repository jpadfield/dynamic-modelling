<?php


$url = 'https://schema.datacite.org/meta/kernel-4.4/metadata.xsd';
$pi = pathinfo ($url);
//prg(1, $pi);

$data = xsd2arr ($url, 1);
$btno = 0;

//prg(1, $data["simpleType"]);

if (isset($data["simpleType"]))
  {echo processSimpleType ($data["simpleType"], $url);}

foreach ($data["include"] as $k => $i)
  {  
  $iurl = $pi["dirname"]."/".$i["@attributes"]["schemaLocation"];
  $ipi = pathinfo ($iurl);

  echo "$url	includes	$ipi[basename]|$iurl\n";
  $idata = xsd2arr ($iurl, 0);
  
  if (isset($idata["simpleType"]))
    {echo simpleType ($idata["simpleType"], $ipi["basename"]);}
  }

exit;

/*if ($str = file_get_contents($file)) {
  prg(0, $str);
  
  $xml = simplexml_load_string($str);
  $arr = object2array($xml);
  
  prg(0, $arr);
} else {
    exit('Failed to open test.xml.');
}*/


function processSimpleType ($arr, $url="")
  {
  $out = "";
  // single item
  if (isset($arr["@attributes"]))  
    {$out .= simpleType ($arr, $url="");}
  else
    {
    foreach ($arr as $k => $std)
      {$out .= simpleType ($std, $url);}      
    }
    
  return ($out);
  }

function simpleType ($std, $url="")
  {  
  global $btno;
  
  $out = "";
  
  $thing = array(
    "name" => "",
    "type" => "",
    "values" => array(),
    "comments" => array(),
    "patterns" => array(),
    "others" => array());
      
  if (isset($std["@attributes"]["name"]) and $std["@attributes"]["name"])
    {$thing["name"] = $std["@attributes"]["name"];
     unset($std["@attributes"]);}
      
  if (isset($std["restriction"]["@attributes"]["base"]) and $std["restriction"]["@attributes"]["base"])
    {$thing["type"] = $std["restriction"]["@attributes"]["base"];
     unset($std["restriction"]["@attributes"]);}
      
  /*if (isset($std["restriction"]["enumeration"]) and $std["restriction"]["enumeration"])
    {foreach ($std["restriction"]["enumeration"] as $ek => $ev)
      {$thing["values"][] = $ev["@attributes"]["value"];}
     unset($std["restriction"]["enumeration"]);}*/
  if (isset($std["restriction"]["enumeration"]))
    {$thing["values"] = thingAttributes ($std["restriction"]["enumeration"], "value");
     unset($std["restriction"]["enumeration"]);}
          
  /*if (isset($std["restriction"]["comment"]) and $std["restriction"]["comment"])
    {foreach ($std["restriction"]["comment"] as $ek => $ev)
      {if ($ev){$thing["comments"][] = $ev["@attributes"]["value"];}}
     unset($std["restriction"]["comment"]);}*/
  if (isset($std["restriction"]["comment"]))
    {$thing["comments"] = thingAttributes ($std["restriction"]["comment"], "comment");
     unset($std["restriction"]["comment"]);}


  /*if (isset($std["restriction"]["pattern"]) and $std["restriction"]["pattern"])
    {
    if (isset($std["restriction"]["pattern"]["@attributes"]) and $std["restriction"]["pattern"]["@attributes"])
      {$thing["patterns"][] = $std["restriction"]["pattern"]["@attributes"]["value"];}
    else {
      foreach ($std["restriction"]["pattern"] as $ek => $ev)
	{if ($ev){$thing["patterns"][] = $ev["@attributes"]["value"];}}
      }
      
    }*/
  if (isset($std["restriction"]["pattern"]))
    {$thing["patterns"] = thingAttributes ($std["restriction"]["pattern"], "pattern");
     unset($std["restriction"]["pattern"]);}
     
  foreach ($std["restriction"] as $k => $a)
    {   
    if (isset($a["@attributes"]["value"]))
      {$thing["others"][$k] = $a["@attributes"]["value"];}
    }
   
  if ($thing["name"])
    {
    if ($url) {
      $out .= "$url\thas metadata\t$thing[name]\n";
      }
    $out .= "$thing[name]\thas type\t$thing[type]#-$btno\n";
    
    $btno++;
    
    // Create nodes for each value
    /*if ($thing["values"])
      {
      foreach ($thing["values"] as $vk => $v)
	{$out .= "$thing[name]\thas possible value\t$v\n";}
      }*/
    
       
    if ($thing["values"])
      {$out .= "$thing[name]\thas possible values\t";
       $out .= implode("<br/>", $thing["values"])."\t|note\n";}
	 
    if ($thing["patterns"])
      {$out .= "$thing[name]\thas format\t".
	implode("<br/>", $thing["patterns"])."\t|note\n";}      
	
    if ($thing["comments"])
      {$out .= "$thing[name]\thas comment\t".
	implode("<br/>", $thing["comments"])."\t|note\n";}
	
    if ($thing["others"])
      {foreach ($thing["others"] as $k => $v)
	{$out .= "$thing[name]\thas metadata\t$k\n".
	  "$k\thas value\t$v\n";}}    
    }
    
  return ($out);    
  }


function thingAttributes ($arr=array(), $label="things")
  {
  $thing = array();
  
  if (isset($arr) and $arr)
    {
    if (isset($arr["@attributes"]) and $arr["@attributes"])
      {$thing[] = $arr["@attributes"]["value"];}
    else {
      foreach ($arr as $k => $v)
	{
	if (isset($v["@attributes"]) and $v["@attributes"]) {
	  $thing[] = $v["@attributes"]["value"];
	  }
	}
      }      
    }
    
  return ($thing);
  }
  
function xsd2arr ($url, $export=false)
  {  
  $doc = new DOMDocument();
  $doc->preserveWhiteSpace = true;
  $doc->load($url);
  $doc->save('t.xml');
  $xmlfile = file_get_contents('t.xml');
  $parseObj = str_replace($doc->lastChild->prefix.':',"",$xmlfile);
  $ob= simplexml_load_string($parseObj);
  $json  = json_encode($ob);
  
  if ($export)
    {
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: *");
    echo $json;  
    exit;
  }
  else
    {$data = json_decode($json, true);
     return ($data);}
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

function getRemoteJsonDetails ($uri, $format=false, $decode=false)
	{if ($format) {$uri = $uri.".".$format;}
	 $fc = file_get_contents($uri);
	 if ($decode)
		{$output = json_decode($fc, true);}
	 else
		{$output = $fc;}
	 return ($output);}


function object2array($object) { return @json_decode(@json_encode($object),1); }	 

/*
 
 titleType|https://schema.datacite.org/meta/kernel-4.4/include/datacite-titleType-v4.xsd	has label	titleType#-1	type|name
titleType	has possible value	AlternativeTitle
titleType	has possible value	Subtitle
titleType	has possible value	TranslatedTitle
titleType	has possible value	Other#-1
titleType	has version history	"Version 1.0 - Created 2011-01-13 - FZ, TIB, Germany<br/> 2013-05 v3.0: Addition of ID to simpleType element<br/> 2015-02-12 v4.0 Added value 'Other'"

contributorType|https://schema.datacite.org/meta/kernel-4.4/include/datacite-contributorType-v4.xsd	has label	contributorType#-1	type|name
contributorType	has comment	The type of contributor of the resource.	|note
contributorType	has possible value	ContactPerson
contributorType	has possible value	DataCollector
contributorType	has possible value	DataCurator
contributorType	has possible value	DataManager
contributorType	has possible value	Distributor
contributorType	has possible value	Editor
contributorType	has possible value	HostingInstitution
contributorType	has possible value	Other
contributorType	has possible value	Producer
contributorType	has possible value	ProjectLeader
contributorType	has possible value	ProjectManager
contributorType	has possible value	ProjectMember
contributorType	has possible value	RegistrationAgency
contributorType	has possible value	RegistrationAuthority
contributorType	has possible value	RelatedPerson
contributorType	has possible value	ResearchGroup
contributorType	has possible value	RightsHolder
contributorType	has possible value	Researcher
contributorType	has possible value	Sponsor
contributorType	has possible value	Supervisor
contributorType	has possible value	WorkPackageLeader
 
 
 */

?>
