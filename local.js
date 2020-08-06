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
