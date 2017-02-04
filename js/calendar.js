/* Пример оформления даты
   date={ on: new Date(), from: new Date('2013',6,18) };
*/
function genCalenTable(id,idate)
{
  var today=new Date();
  var dFrom=idate.from;
  var tyear=nyear(today),tmonth=today.getMonth(),tdate=today.getDate();
  var month=idate.on.getMonth(); 
  var year=nyear(idate.on);
  var date=1;
  var UDate=new Date(year,month,date);
  str='<table id="calendar" class="calendar">\n'
     +' <thead>\n'
     +'  <tr>\n'
     +'   <th colspan="7" class="monthselect">\n'
     +'   \n';
  // Переход влево
  if( nyear(idate.from)==year && month<=idate.from.getMonth() )
  {
    str+='     <span style="color:red;">&lt;&lt;</span>\n';
   }
  else
  {
    var cdate=new Date(year,month-1,1);
    var cyear=year; if( month<=0 ) cyear--;
    var dateLeft='"toolTip();'
                 +' genCalenTable(\''+id
                 +'\',{'
                 +'on: new Date('+nyear(cdate)+','+cdate.getMonth()+',1),'
                 +'from: new Date('+nyear(dFrom)+','
                 +dFrom.getMonth()+','+dFrom.getDate()+')'
                 +'});"';
    str+='     <a class="monthlink" href="javascript:void(0);" onclick='+dateLeft
        +'>&lt;&lt;</a>\n';
   }
  // Прорисовываем дату
  str+='     '+nmonth(month)+'&nbsp;'+year+'\n';
  // Переход вправо
  if( tyear==year && month>=today.getMonth() )
  {
    str+='     <span style="color:red;">&gt;&gt;</span>\n';
   }
  else
  {
    var cdate=new Date(year,1+month,1);
    var cyear=year; if( month>=11 ) cyear++;
    var dateRight='"toolTip();'
                 +' genCalenTable(\''+id
                 +'\',{'
                 +'on: new Date('+nyear(cdate)+','+cdate.getMonth()+',1),'
                 +'from: new Date('+nyear(dFrom)+','
                 +dFrom.getMonth()+','+dFrom.getDate()+')'
                 +'});"';
    str+='     <a class="monthlink" href="javascript:void(0);" onclick='+dateRight+'\n'
        +'>&gt;&gt;</a>\n';
   }
  // Прорисовываем дни недели в заголоыке
  str+='   \n'
      +'   </td>\n'
      +'  </tr>\n'
      +'  <tr>\n'
      +'    <th class="workday">Пн</th>\n'
      +'    <th class="workday">Вт</th>\n'
      +'    <th class="workday">Ср</th>\n'
      +'    <th class="workday">Чт</th>\n'
      +'    <th class="workday">Пт</th>\n'
      +'    <th class="workday">Сб</th>\n'
      +'    <th class="workday">Вс</th>\n'
      +'  </tr>\n'
      +' </thead>\n'
      +' <tbody>\n';
  var iday=nday(UDate.getDay());
  if( iday!=0 )
  {
    str+='  <tr>\n'
        +'   <td align="center" colspan="'+iday+'">'
        
        +'</td>\n';
   }
  while( UDate.getMonth()==month )
  {
    iday=nday(UDate.getDay());
    if( iday==0 )
    {
      str+='  </tr>\n'
          +'  <tr>\n';
     }
    var datstr=date+' '+nmonth(month,1)+' '+year; 
    if( tyear==year && tmonth==month && tdate<date )
    {
      str+='   <td align="center"'
          +'<span style="color:#ccc;">'+date+'</span></td>\n';
     }
	 else
    {
		if(tyear==year && tmonth==month && tdate==date)
		 {
			 str+='   <td class="day-active-v day-current">'
			  +'<a class="day-active-v" href="/news/?history='+year+'-'+(month/1+1)+'-'+date+'">'+date+'</a></td>\n';
		 }
		 else
		 {
		  str+='   <td class="day-active-v">'
			  +'<a class="day-active-v" href="/news/?history='+year+'-'+(month/1+1)+'-'+date+'">'+date+'</a></td>\n';
		 }
     }
	 
    
    date++;  UDate=new Date(year,month,date);
   }
  if( iday<6 ) 
  {
    str+='   <td align="center" colspan="'+(6-iday)+'">'
        
        +'</td>\n';
   }
  str+='  </tr>\n';
  str+=' </tbody>\n'
      +'</table>\n';
  //alert(str);
  $('#'+id).html();
  $('#'+id).html(str);
  return;

  /* Всякие полезные функциюжки */
  // Нормальный год
  function nyear(date)
  { 
    var year=date.getYear(); year=year%100; return (2000+year);
   }
  // Имена месяцев
  function nmonth(month,gen)
  {
    var nomin=
    {
      0:'Январь', 1:'Февраль', 2:'Март',
      3:'Апрель', 4:'Май', 5:'Июнь',
      6:'Июль', 7:'Август', 8:'Сентябрь',
      9:'Октябрь', 10:'Ноябрь', 11:'Декабрь'
     }
    var genit=
    {
      0:'января', 1:'февраля', 2:'марта',
      3:'апреля', 4:'мая', 5:'июня',
      6:'июля', 7:'августа', 8:'сентября',
      9:'октября', 10:'ноября', 11:'декабря'
     }
    if( month<0 ) month=11;
    if( month>11 ) month=0;
    if( gen ) return genit[month];
    return nomin[month];
   }
  // Нормальная нумерация дней недели
  function nday(day)
  { 
    if( day==0 ) return 6;
    return day-1;
   } 

 }    

