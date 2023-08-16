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

var panZoom;

function modelZoom ()
    {
    console.log("modelZoom"); 
    console.log($('zoom-toggle'));   
    
    if (typeof panZoom !== 'undefined') {
      // Variable is defined
      // Your code here
      panZoom.destroy();
      panZoom = undefined;
      $(window).off('resize'); 
      $('#zoom-toggle').prop('checked', false);
      } 
    else {
      // Variable is not defined
      // Your code here
      var md = $( "svg" ).first();      
      $(md).width("100%");
      $(md).height("100%");
    
      // Expose to window namespase for testing purposes
      panZoom = svgPanZoom('#' + $(md).attr('id'), {
        zoomEnabled: true,
        minZoom: 0.1,
        maxZoom: 75,
        controlIconsEnabled: true,
        fit: true,
        center: true
        });
        
      $('#zoom-toggle').prop('checked', true);
      
      $(window).resize(function(){
        modelResize ();
        });    
      
        }    
    }
    
function modelResize ()
  {
  console.log("modelResize");
  panZoom.resize();
  panZoom.fit();
  panZoom.center();  
  }
    
function togglefullscreen (b, divID)
  {
  var src = $('#'+b).children('img')[0].src;
  var filename = src.substring(src.lastIndexOf('/')+1);

  if (filename == "view-fullscreen.png") {
    $('#'+b).html("<img src=\"graphics/view-restore.png\" width=\"26\" />"); }
  else {
    $('#'+b).html("<img src=\"graphics/view-fullscreen.png\" width=\"26\" />");  }
      
  $('#'+divID).toggleClass('fullscreen');
  $(':focus').blur();
  modelResize ();
  }


$(document).ready(function()
  {
  ///////////////////////////////////////////////////////////////////////
  // Based on https://bbbootstrap.com/snippets/modal-multiple-tabs-89860645
  ///////////////////////////////////////////////////////////////////////
          
  $(".tabs").click(function(){
    $(".tabs").removeClass("active");
    $(".tabs h6").removeClass("font-weight-bold");
    $(".tabs h6").addClass("text-muted");
    $(this).children("h6").removeClass("text-muted");
    $(this).children("h6").addClass("font-weight-bold");
    $(this).addClass("active");

    current_fs = $(".active");
    next_fs = $(this).attr('id');
    next_fs = "#" + next_fs + "1";

    $("fieldset").removeClass("show");
    $(next_fs).addClass("show");

    current_fs.animate({}, {
      step: function() {
        current_fs.css({
          'display': 'none',
          'position': 'relative'
          });
        next_fs.css({
          'display': 'block'
          });
        }
      });
    });
  //////////////////////////////////////////////////////////////////////
  
  $(document).ready(function() {
        $("#mermaidCode").click(function(e) {
            e.preventDefault(); // Prevent the default link behavior
            
            var htmlContent = $("#modelDivTxt").html(); // Get the HTML content to copy
            var decodedContent = $("<textarea>").html(htmlContent).text(); // Decode HTML entities
            var tempInput = $("<textarea>"); // Create a temporary textarea element
            $("body").append(tempInput);
            tempInput.val(decodedContent).select(); // Set the decoded content and select it
            document.execCommand("copy"); // Copy the selected content to the clipboard
            tempInput.remove(); // Remove the temporary textarea
            //alert("Content copied to clipboard:\n" + decodedContent);
        });
    });
    
  //////////////////////////////////////////////////////////////////////
  // Compressing data and AJAX calls
  //////////////////////////////////////////////////////////////////////
    
  if (pcode) {
    const d1 = Base64.toUint8Array(pcode);
    const d2 = pako.inflate(d1, { to: 'string' })
    console.log("Pako String");
    processTriples (d2);    
    }
   else {
    f1(); 
    }
	const bmCompressed = pako.deflate($("#triplesTxt").text().trim(), { level: 9 });
	const bmData = Base64.fromUint8Array(bmCompressed, true);
	const bmURL = "./?data=pako:"+bmData;
	$("#bookmark").attr("href", bmURL);
	
  const compressed = pako.deflate(code, { level: 9 });
  const send = Base64.fromUint8Array(compressed, true) 
  const mleURL = "https://mermaid-js.github.io/mermaid-live-editor/edit#pako:"+send;
  const imURL = "./?image="+send;
  $("#mermaidLink").attr("href", mleURL);
  $("#downloadLink").attr("href", imURL);
    
});

function resolveAfterTime(x) {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve(x);
    }, x);
  });
}

async function f1() {
  const x = await resolveAfterTime(1000);
  modelZoom();
}

async function f2(period) {
  const x = await resolveAfterTime(period);
}

function processTriples (triples)
  { 
  console.log("processTriples");
  //console.log(triples);
  $.ajax({ method: "POST", url: "index.php", 
      data: {'triples': triples},
      }).done(function( data ) { 
      //console.log(data.triples);
      //var result = $.parseJSON(data); 
      $("#triplesTxt").html(data.triples);
      $("#modelDiv").html(data.mermaid);

      const bmCompressed = pako.deflate($("#triplesTxt").text().trim(), { level: 9 });
      const bmData = Base64.fromUint8Array(bmCompressed, true);
      const bmURL = "./?data=pako:"+bmData;
      $("#bookmark").attr("href", bmURL);
      
      f1();
      $( "#refreshM" ).trigger( "click" );
      //console.log(data);  
      });     
  }
