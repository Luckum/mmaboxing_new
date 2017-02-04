
definegrid = function() {
  var browserWidth = $(window).width();
  if (browserWidth > 640) {
    pageUnits = 'px';
    colUnits = 'px';
    pagewidth = 1180;
    columns = 15;
    columnwidth = 60;
    gutterwidth = 20;
    pagetopmargin = 20;
    rowheight = 20;
    gridonload = 'off';
    makehugrid();
  }
  if (browserWidth >= 1270) {
    pageUnits = 'px';
    colUnits = 'px';
    pagewidth = 1220;
    columns = 16;
    columnwidth = 60;
    gutterwidth = 20;
    pagetopmargin = 20;
    rowheight = 20;
    gridonload = 'off';
    makehugrid();
  }

}

$(document).ready(function() {
  definegrid();
  setgridonload();
});

$(window).resize(function() {
  definegrid();
  setgridonresize();
});