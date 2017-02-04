// http://www.focusoff.ru/razrabotchikam/scripts/29-tooltip.html
// ������ ���� � ����� http://www.focusoff.net
//
// ��������� ������� ��� ������ ��� ��������:
// ���������� ������, ���������� ����������, �������� �� ���������.
// onMouseOver="toolTip('tool tip text here')";
// onMouseOut="toolTip()";
// ��� ���������� ������ ������, ����� ������� � ����� �����������:
// �����, ���� ������, ���� ����
// onMouseOver="toolTip('��� ����� �����', '#FFFF00', 'orange')";
// onMouseOut="toolTip()";
// ������ ������������� �������:
// <span onmouseout="toolTip()" onmouseover="toolTip('������ � ������ ToolTip','red','#FFFFFF')">�������� ������ ���� �� ��� �������</span>

/*
���������� ���� ��� ������ ����<body>, ����� ����� ����� ���� 
<div id="toolTipLayer" style="position:absolute; visibility: hidden"></div>
<script language="JavaScript"><!--
initToolTips(); //--></script>
*/
var ns4=document.layers;
var ns6=document.getElementById && !document.all;
var ie4=document.all;
var vmsg='',cinf='';
offsetX=0; offsetY=20;
var toolTipSTYLE="";
function initToolTips()
{
  if( ns4||ns6||ie4 )
  {
    msg='';
    if( ns4 ) toolTipSTYLE=document.toolTipLayer;
    else if( ns6 ) toolTipSTYLE=document.getElementById("toolTipLayer").style;
    else if( ie4 ) toolTipSTYLE=document.all.toolTipLayer.style;
    if( ns4 ) document.captureEvents(Event.MOUSEMOVE);
    else
    {
      toolTipSTYLE.visibility="visible"; toolTipSTYLE.display="none";
     }
    document.onmousemove=moveToMouseLoc;
   }
  return toolTipSTYLE;
 }

function toolTip(msg,fg,bg)
{
  try
  {
  if( toolTip.arguments.length<1 ) 
  { // ���� ������� toolTip ������ - �������� ���������� ����
    if( ns4 ) toolTipSTYLE.visibility="hidden";
    else toolTipSTYLE.display="none";
    vmsg=''; 
    return;
   }
  // ���� �������� - ���������� ����
  if( vmsg!='' ) return;
  vmsg=msg;
  if( !fg ) fg="#000000"; // ���� ������ �� ���������
  if( !bg ) bg="#FFFFFF"; // ���� ���� �� ���������
  var content=
      '<table border="solid 1px" cellspacing="0" cellpadding="1" bgcolor="'+fg+'">'
      +'<td>' // ������ ���������� ���� ���������
      +'<table border="0" cellspacing="0" cellpadding="1" bgcolor="'+bg+'">'
      +'<td align="center">'
      +' <font face="sans-serif" color="'+fg+'" size="-2">&nbsp\;'+msg+'&nbsp\;</font>'
      +'</td></table></td></table>';										//
  if( ns4 )
  {
    toolTipSTYLE.document.write(content);
    toolTipSTYLE.document.close();
    toolTipSTYLE.visibility="visible";
   }
  if( ns6 )
  {
    document.getElementById("toolTipLayer").innerHTML=content;
    toolTipSTYLE.display='block';
   }
  if( ie4 )
  {
    document.all("toolTipLayer").innerHTML=content;
    toolTipSTYLE.display='block';
   }
   }
  catch(e){ alert('toolClick\n'+e); }
 }

function toolClick()
{
  try
  {
  if( !document.all ) return;
  fg="#000000"; // ���� ������ �� ���������
  bg="#FFFFFF"; // ���� ���� �� ���������
  var content='<table border="solid 1px" cellspacing="0" cellpadding="1" bgcolor="'+fg+'">'
             +'<td>' // ������ ���������� ���� ���������
             +'<table border="0" cellspacing="0" cellpadding="1" bgcolor="'+bg+'">'
             +'<td align="center">'
             +' <font face="sans-serif" color="'+fg+'" size="-2">&nbsp\;&nbsp\;</font>'
             +'</td></table></td></table>';										//
  oldHtml=document.all("toolTipLayer").innerHTML;
  oldStyle=toolTipSTYLE.display;
  document.all("toolTipLayer").innerHTML=content;
  toolTipSTYLE.display='block';
  document.all("toolTipLayer").innerHTML=oldHtml;
  toolTipSTYLE.display=oldStyle;
   }
  catch(e){ alert('toolClick\n'+e); }
 }

function moveToMouseLoc(e)
{
  var el,dbsl,dbst,x,y,xr,yr,s='';
  if( ns4||ns6 )
  { 
    x=e.pageX; y=e.pageY; 
    xr=e.clientX; yr=e.clientY;
    el=e.target; e.stopPropagation();
   }
  else
  {
    dbsl=document.body.scrollLeft;  dbst=document.body.scrollTop;
    x=event.x+dbsl; y=event.y+dbst;
    xr=event.clientX; yr=event.clientY;
    el=event.srcElement; event.cancelBubble=true;
   }
  offx=offsetX; offy=offsetY;
  if( el )
  {
    var br,xf,yf,cc=0;  
    try{ br=el.getBoundingClientRect(); } catch(e){ return true; }
    yf=br.bottom; xf=br.right;
    xa=document.body.scrollWidth; ya=document.body.scrollHeight;
    if( (y+offsetY)>=(ya-offsetY) ){ offy=-offsetY; cc=1; }
    if( (x+20)>=(xa-20) ){ offx=-20; cc=1; }
/*
    if( (yr+offsetY)>=(yf-offsetY) ){ offy=-offsetY; cc=1; }
    if( (xr+20)>=(xf-20) ){ offx=-20; cc=1; }
*/
    if( cc )
    {
      if( el.id ) s+=el.id+':\n';
      s+='X0:'+x+', Y0:'+y+'\n';
      s+='XA:'+document.body.scrollWidth+', YA:'+document.body.scrollHeight+'\n';
      s+='XF:'+xf+', YF:'+yf+'\n';
      s+='XR:'+xr+', YR:'+yr;
      //alert(s); 
     }
   }
  toolTipSTYLE.left=x+offx; toolTipSTYLE.top=y+offy;
  return true;
 }

function getPageEventCoords(evt)
{
  if( !evt ) evt=window.event;
  var coords={left:0,top:0};
  if( evt.pageX )
  {
    coords.left=evt.pageX; coords.top=evt.pageY;
   }
  else if( evt.clientX )
  {
    coords.left=evt.clientX+document.body.scrollLeft-document.body.clientLeft; 
    coords.top=evt.clientY+document.body.scrollTop-document.body.clientTop;
    if( document.body.parentElement && document.body.parentElement.clientLeft )
    { 
      var bodParent=document.body.parentElement;
      coords.left+=bodParent.scrollLeft-bodParent.clientLeft;
      coords.top+=bodParent.scrollTop-bodParent.clientTop;
     }        
   }
  return coords;    
 }     

function getElementPosition(elemId)
{ var elem=((typeof elemId=='object')?elemId:document.getElementById(elemId));
  var w=elem.offsetWidth,h=elem.offsetHeight,l=0,t=0;
  while( elem )
  {
    l+=elem.offsetLeft; t+=elem.offsetTop; elem=elem.offsetParent;
   }
  return{"left":l,"top":t,"width":w,"height":h};
 }

function getClientWidth()
{
  var cw=document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientWidth:document.body.clientWidth;
  return cw; 
 }

function getClientHeight()
{
  var ch=document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientHeight:document.body.clientHeight;
  return ch;
 }