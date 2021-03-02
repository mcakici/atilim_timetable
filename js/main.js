var activesubjectlist = 0, extraquery = '', _get = '';

$(function () {
	_get = $('body').data('qstring');

	gethtml('ajax.php?i=getsubjects'+_get, function(response){ $('.tablecontainer').html(response); get_subjectlist(0); });
	
	loading(0);
	
	var uri = window.location.toString();
	if (uri.indexOf("?") > 0) {
	    var clean_uri = uri.substring(0, uri.indexOf("?"));
	    window.history.replaceState({}, document.title, clean_uri);
	}
	
	
	$('#preventconflict').on('change', function() {
		var $this = $(this);
		$.cookie("_preventconflict", ($this.is(':checked') ? 1 : 0), {expires:365, path: '/'});
		get_subjectlist();
	});
	
	
	$('select.selectClass').on('change', function() {
		var $this = $(this);

		var selected = $this.find(':selected').attr('value');
		gethtml('ajax.php?i=getsubjects&classid='+selected, function(response){ $('.tablecontainer').html(response); });
		_get = '&classid='+selected;
		$('.selectTeacher').prop('selectedIndex', 0).selectpicker('refresh');
		$('.selectClassroom').prop('selectedIndex', 0).selectpicker('refresh');

	});
	
	$('select.selectClassroom').on('change', function() {
		var $this = $(this);

		var selected = $this.find(':selected').attr('value');
		gethtml('ajax.php?i=getsubjects&classroomid='+selected, function(response){ $('.tablecontainer').html(response); });
		_get = '&classroomid='+selected;
		
		$('.selectClass').prop('selectedIndex', 0).selectpicker('refresh');
		$('.selectTeacher').prop('selectedIndex', 0).selectpicker('refresh');

	});
	
	$('select.selectTeacher').on('change', function() {
		var $this = $(this);

		var selected = $this.find(':selected').attr('value');
		gethtml('ajax.php?i=getsubjects&teacherid='+selected, function(response){ $('.tablecontainer').html(response); });
		_get = '&teacherid='+selected;
		
		$('.selectClass').prop('selectedIndex', 0).selectpicker('refresh');
		$('.selectClassroom').prop('selectedIndex', 0).selectpicker('refresh');
	});
	
	
	//kayıtlı departmanlar
	if( $.cookie("_department") ){
		var myarr = $.cookie("_department").split(",");
		$.each(myarr, function( index, value ) {
			$("select.selectDepartment option[value='"+value+"']").prop("selected",true);
			$("select.selectDepartment").selectpicker("refresh");
		});
	}
	
	$('select.selectDepartment').on('change', function() {
		var $this = $(this);
		
		$.cookie("_department", $.toJSON($this.val()), {expires:365, path: '/'});
		get_subjectlist(0);
	});
	
	$('select.selectView').on('change', function() {
		var $this = $(this);		
		$.cookie("_showintable", $.toJSON($this.val()), {expires:365, path: '/'});
		gethtml('ajax.php?i=getsubjects'+_get, function(response){ $('.tablecontainer').html(response);});
	});
	

	$(document).on("contextmenu",".subjectitem",function(e) {
		var thisone = $(e.target);
		if(thisone.is("small,b,span,i")) thisone = thisone.parents(".subjectitem");
		blinksubject(thisone);
		console.log(thisone.data("lcode"));
		if($('button.btnedit').length > 0)
		$('#contextMenu').html('<a class="dropdown-item" href="#" onclick="get_bologna_page(\''+thisone.data("lcode")+'\');"><i class="fa fa-external-link" aria-hidden="true"></i> '+thisone.data("lcode")+' Bologna</a>');
		else
		$('#contextMenu').html('<a class="dropdown-item" href="#" onclick="removeOne(\''+thisone.data("id")+'\');"><i class="fa fa-eraser" aria-hidden="true"></i> Just delete this one</a>\
		<a class="dropdown-item" href="#" onclick="removesubject(\''+thisone.data("lcode")+'\',\''+thisone.data("section")+'\');"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete this course</a>\
		<div class="dropdown-divider"></div>\
		<a class="dropdown-item" href="#" onclick="get_bologna_page(\''+thisone.data("lcode")+'\');"><i class="fa fa-external-link" aria-hidden="true"></i> '+thisone.data("lcode")+' Bologna</a>');
		
		$('#contextMenu').css({
			display: "block",
			left: e.pageX - 10,
			top: e.pageY - 10
		}).show();
		return false; //blocks default Webbrowser right click menu
	}).on("click",function(){
		$("#contextMenu").hide();
	});
	
	$(document).on("dblclick",".subjectitem",function() {
		var thisone = $(this);
		get_bologna_page(thisone.data("lcode"));
	});
	
	$(document).on("click","input.copythisone",function() {
		var thisone = $(this);
		thisone.select();
		document.execCommand('copy');
		thisone.tooltip({ title:'Copied!' }).tooltip('show');
		setTimeout(function(){ thisone.tooltip('dispose'); },1000);
	});
	
});

function get_bologna_page(lcode){
	if(!lcode) return;
	loading(1);
	$.ajax({
		type: 'GET',
		url: 'ajax.php?i=bologna&lcode='+lcode,
		success: function(cevap) {
			if(cevap) window.open(cevap,'_blank');
			loading(0);
		}
	});
}

function openInNewTab(url) {
	$("<a>").attr("href", url).attr("target", "_blank")[0].click();
}

function refresh_table(){
	_get = '&';
	gethtml('ajax.php?i=getsubjects', function(response){ $('.tablecontainer').html(response);});
	$('.selectClass').prop('selectedIndex', 0).selectpicker('refresh');
	$('.selectTeacher').prop('selectedIndex', 0).selectpicker('refresh');
	$('.selectClassroom').prop('selectedIndex', 0).selectpicker('refresh');
}

function useNewData(){
	if(data.length) $.cookie("_selectedsubjects", data, {expires:365, path: '/'});
	if(exclude.length) $.cookie("_removedsubjects", exclude, {expires:365, path: '/'});
	gethtml('ajax.php?i=getsubjects', function(response){ $('.tablecontainer').html(response);});
	get_subjectlist();
	$('.selectClass').prop('selectedIndex', 0).selectpicker('refresh');
}

function get_subjectlist(newquery){
	if(newquery) extraquery = newquery;
	$.cookie("_usedPeriods", $.toJSON(getUsedPeriods()), {expires:365, path: '/'});
	gethtml('ajax.php?i=searchlessons'+(extraquery ? extraquery : ''),function(response){ $('#subjectlist').html(response); quick_search(); /*$('#quicksearchinput').val('');*/	});	
}



function quick_search() {
	var input, filter, ul, li, a, i;
	input = document.getElementById("quicksearchinput");
	filter = input.value.toUpperCase();
	ul = document.getElementById("subjectlist");
	li = ul.getElementsByTagName("li");
	for (i = 0; i < li.length; i++) {
			a = li[i].getElementsByTagName("a")[0];
			if (a.innerHTML.toUpperCase().indexOf(filter) > -1) {
					li[i].style.display = "";
			} else {
					li[i].style.display = "none";
			}
	}
	if(input.value.length > 0){
		input.classList.add("bg-warning");
	}else{
		input.classList.remove("bg-warning");
	}
}


function showExamhours(form){
	var thisone = $(form);
	gethtml('ajax.php?i=showexamhours', function(response){ $('.tablecontainer').html(response); }, thisone.serialize());
}


function blinksubject(elem){
	$(elem).fadeOut(250).fadeIn(250);
	$(".subjectitem").removeClass("subjectfocused");
	$(elem).addClass("subjectfocused");
	
	/*
	$('.subjectperiod[data-count]').filter(function () {
		return $(this).data('count') > 1;
	}).css("background-color", " rgba(0, 0, 0, 0.5)");
	*/

}

function getUsedPeriods(){
	var _filledPeriods = [];
	calculateNewDataCounts();
	//table td 5x9 iptal -> new 6x11
	for(var sp=0; sp<66; sp++){
		var sbp = $("table td.subjectperiod").eq(sp);
		if(sbp.data("count") > 0)
		_filledPeriods.push({period:sbp.data("period"),day:sbp.data("day"),count:sbp.data("count"),dp:sbp.data("day")+''+sbp.data("period")});
	}
	return _filledPeriods;
}

function searchlesson(elem){
	var thisone = $(elem);
	/*
	var _filledPeriods = [];
	//table td 5x9 iptal
	for(var sp=0; sp<45; sp++){
		var sbp = $("table td.subjectperiod").eq(sp);
		if(sbp.data("count") > 0)
		_filledPeriods.push({period:sbp.data("period"),day:sbp.data("day"),count:sbp.data("count"),dp:sbp.data("day")+''+sbp.data("period")});
	}
	*/
	
	var period = thisone.closest(".subjectperiod").data("period"),
	day = thisone.closest(".subjectday").data("day")
	maxperiod = 0;
	
	for(var i=period; i<10; i++){
		if($("tr.subjectday[data-day='"+day+"'] td.subjectperiod[data-period='"+i+"']").data("count") < 1) maxperiod = i; else break;
	}
	get_subjectlist('&period='+period+'&day='+day+'&maxperiod='+maxperiod);
}




function addsubject(subcode,subsection,elem){
	var _selectedsubjects = [];
	if($.cookie("_selectedsubjects") != null)  _selectedsubjects = jQuery.parseJSON($.cookie("_selectedsubjects"));
	for(var i=0; i<_selectedsubjects.length; i++){
		if(_selectedsubjects[i]['code'] == subcode && _selectedsubjects[i]['section'] == subsection){
			if($(".subject"+subcode+subsection).length < 1){
				deleteFromRemovedSubjectArray(subcode,subsection);
				for(var j=0; j<_selectedsubjects.length; j++){
					if(_selectedsubjects[j]['code'] == subcode && _selectedsubjects[j]['section'] == subsection){
						_selectedsubjects.splice(i, 1);
						break;
					}
				}
				
			}else{
				blinksubject(".subject"+subcode+subsection);
				return false;
			}
		}
	}
	$('.tablecontainer').empty();
	
	
	_selectedsubjects.push({code:subcode,section:parseInt(subsection)});
	
	if(_selectedsubjects.length) $.cookie("_selectedsubjects", $.toJSON(_selectedsubjects), {expires:365, path: '/'});
	
	gethtml('ajax.php?i=getsubjects', function(response){ $('.tablecontainer').html(response);  blinksubject(".subject"+subcode+subsection);
			if($.cookie("_preventconflict") == 1) get_subjectlist();  });
}

function removeSelectedSubjectCookies(){
	$.removeCookie('_selectedsubjects', { path: '/' });
	$.removeCookie('_usedPeriods', { path: '/' });
	$.removeCookie('_removedsubjects', { path: '/' });
	gethtml('ajax.php?i=getsubjects', function(response){ $('.tablecontainer').html(response); get_subjectlist(); });
}

function removeRemovedSubjectCookies(){
	$.removeCookie('_removedsubjects', { path: '/' });
	gethtml('ajax.php?i=getsubjects', function(response){ $('.tablecontainer').html(response); get_subjectlist(); });
}

function get_dayname(val){
	var out;
	switch(val){
		case '1': out = "Mon"; break;
		case '2': out = "Tue"; break;
		case '3': out = "Wed"; break;
		case '4': out = "Thu"; break;
		case '5': out = "Fri"; break;
		case '6': out = "Sat"; break;
		case '7': out = "Sun"; break;
	}
	return out;
}

function get_hour(val){
	var out;
	switch(val){
		case '1': out = "09:30 - 10:20"; break;
		case '2': out = "10:30 - 11:20"; break;
		case '3': out = "11:30 - 12:20"; break;
		case '4': out = "12:30 - 13:20"; break;
		case '5': out = "13:30 - 14:20"; break;
		case '6': out = "14:30 - 15:20"; break;
		case '7': out = "15:30 - 16:20"; break;
		case '8': out = "16:30 - 17:20"; break;
		case '9': out = "17:30 - 18:20"; break;
	}
	return out;
}

function showRemovedSubjects(){
	if($.cookie("_removedsubjects") != null){ var _removedsubjects = jQuery.parseJSON($.cookie("_removedsubjects")); }else{ $('.remsubdiv').empty; return;}
	if(_removedsubjects.length < 1) return;
	
	var _out = '<i>Some lesson hours removed from table:</i><br>';
	for(var i=0; i<_removedsubjects.length; i++){
		for(var j=0; j<_removedsubjects[i]['dp'].length; j++){
			var dpp = (_removedsubjects[i]['dp'][j])+'';
			_out += _removedsubjects[i]['c']+(_removedsubjects[i]['s'] ? '-'+_removedsubjects[i]['s'] : '')+' '+get_dayname(dpp.substr(0,1))+' '+get_hour(dpp.substr(1,1))+'<br>';
		}
	}
	_out += "<a class='text-secondary mt-2 d-block' href='#' onclick='removeRemovedSubjectCookies();return false;'>>Clear all<</a>";
	$('.remsubdiv').html(_out);
	
}

function removeOne(elemid){
	var elem = $(".subjectitem[data-id='"+elemid+"']");
	var maintd = elem.closest("td.subjectperiod");
	//console.log(elemid);
	var _removedsubjects = [];
	if($.cookie("_removedsubjects") != null) _removedsubjects = jQuery.parseJSON($.cookie("_removedsubjects"));
	var _removedLessonFound = false;
	if(_removedsubjects.length > 0){
		for(var i=0; i<_removedsubjects.length; i++){
			if(_removedsubjects[i]['c'] == elem.data('lcode') && _removedsubjects[i]['s'] == elem.data('section')){
				_removedLessonFound = true;
				var _removedDPFound = false;
				for(var j=0;j<_removedsubjects[i]['dp'].length;j++){
					if(_removedsubjects[i]['dp'][j] == (maintd.data("day")+''+maintd.data("period")) ){
						_removedDPFound = true;
					}
				}
				if(!_removedDPFound){ _removedsubjects[i]['dp'].push( (maintd.data("day")+''+maintd.data("period")) ); break; }
			}
		}
	}
	if(!_removedLessonFound)
	_removedsubjects.push( {c:elem.data('lcode'),s:parseInt(elem.data('section')),dp:[maintd.data("day")+''+maintd.data("period")] } );
	$.cookie("_removedsubjects", $.toJSON(_removedsubjects), {expires:365, path: '/'});
	
	
	maintd.data("count",(parseInt(maintd.data("count"))-1));
	elem.remove();
	
	if(maintd.find(".subjectitem").length < 1)
	maintd.find('div').empty().append('<div onclick="searchlesson(this);" class="noprint" style="width:100%;height:100%;color:rgba(255,255,255,0.05);font-size:20px;padding:15px;vertical-align:middle;overflow:hidden;cursor:pointer;"><i class="fa fa-search"></i></div>');
	
	
	calculateNewDataCounts();
	showRemovedSubjects();
	
	if($.cookie("_preventconflict") == 1) get_subjectlist();
	//gethtml('ajax.php?i=getsubjects', function(response){ $('.tablecontainer').html(response); if($.cookie("_preventconflict") == 1) get_subjectlist(); });
	return false;
}

function calculateNewDataCounts(){
	$("td.subjectperiod").each(function( index ) {
		var thisone = $(this);
		thisone.data("count",(thisone.find(".subjectitem").length));
	});
}

function deleteFromRemovedSubjectArray(c,s){
	var _removedsubjects = [];
	if($.cookie("_removedsubjects") != null) _removedsubjects = jQuery.parseJSON($.cookie("_removedsubjects"));

	for(var i=0; i<_removedsubjects.length; i++){
		if(_removedsubjects[i]['c'] == c && _removedsubjects[i]['s'] == s){
			_removedsubjects.splice(i, 1);
		}
	}
	$.cookie("_removedsubjects", $.toJSON(_removedsubjects), {expires:365, path: '/'});
}

function removesubject(subcodename,subsection){
	var _selectedsubjects = [];
	if($.cookie("_selectedsubjects") != null) _selectedsubjects = jQuery.parseJSON($.cookie("_selectedsubjects")); else return;

	for(var i=0; i<_selectedsubjects.length; i++){
		if(_selectedsubjects[i]['code'] == subcodename && _selectedsubjects[i]['section'] == subsection){
			_selectedsubjects.splice(i, 1);
			break;
		}
	}
	$.cookie("_selectedsubjects", $.toJSON(_selectedsubjects), {expires:365, path: '/'});
	

	deleteFromRemovedSubjectArray(subcodename,subsection);


	gethtml('ajax.php?i=getsubjects', function(response){ $('.tablecontainer').html(response); if($.cookie("_preventconflict") == 1) get_subjectlist(); });
}

function gethtml(url, callback, postdata) {
	loading(1);
	$.ajax({
		type: 'POST',
		url: url,
		data: (typeof postdata !== undefined ? postdata : null),
		async: true,
		success: function(cevap) {
			callback(cevap);
			loading(0);
		}
	});
}

function loading(status){
	if(status){
		$('[data-toggle="tooltip"], .tooltip').tooltip("hide");
		$('.loader').show();
	}else{
		if($('[data-toggle="tooltip"]').length > 0) $('[data-toggle="tooltip"]').tooltip({ delay: { "show": 800, "hide": 0 } });
		if($('select.selectpicker').length > 0) $('select.selectpicker').selectpicker();
		$('.loader').hide();
		showRemovedSubjects();
	}
}

/*cookie*/
!function(e){"function"==typeof define&&define.amd?define(["jquery"],e):"object"==typeof exports?module.exports=e(require("jquery")):e(jQuery)}(function(e){function n(e){return u.raw?e:encodeURIComponent(e)}function o(e){return u.raw?e:decodeURIComponent(e)}function i(e){return n(u.json?JSON.stringify(e):String(e))}function t(e){0===e.indexOf('"')&&(e=e.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{return e=decodeURIComponent(e.replace(c," ")),u.json?JSON.parse(e):e}catch(n){}}function r(n,o){var i=u.raw?n:t(n);return e.isFunction(o)?o(i):i}var c=/\+/g,u=e.cookie=function(t,c,s){if(arguments.length>1&&!e.isFunction(c)){if(s=e.extend({},u.defaults,s),"number"==typeof s.expires){var a=s.expires,d=s.expires=new Date;d.setMilliseconds(d.getMilliseconds()+864e5*a)}return document.cookie=[n(t),"=",i(c),s.expires?"; expires="+s.expires.toUTCString():"",s.path?"; path="+s.path:"",s.domain?"; domain="+s.domain:"",s.secure?"; secure":""].join("")}for(var f=t?void 0:{},p=document.cookie?document.cookie.split("; "):[],l=0,m=p.length;m>l;l++){var x=p[l].split("="),g=o(x.shift()),j=x.join("=");if(t===g){f=r(j,c);break}t||void 0===(j=r(j))||(f[g]=j)}return f};u.defaults={},e.removeCookie=function(n,o){return e.cookie(n,"",e.extend({},o,{expires:-1})),!e.cookie(n)}});
$.cookie.raw = true;
/*! jQuery JSON plugin v2.5.1 | github.com/Krinkle/jquery-json */
!function($){"use strict";var escape=/["\\\x00-\x1f\x7f-\x9f]/g,meta={"\b":"\\b","	":"\\t","\n":"\\n","\f":"\\f","\r":"\\r",'"':'\\"',"\\":"\\\\"},hasOwn=Object.prototype.hasOwnProperty;$.toJSON="object"==typeof JSON&&JSON.stringify?JSON.stringify:function(a){if(null===a)return"null";var b,c,d,e,f=$.type(a);if("undefined"===f)return void 0;if("number"===f||"boolean"===f)return String(a);if("string"===f)return $.quoteString(a);if("function"==typeof a.toJSON)return $.toJSON(a.toJSON());if("date"===f){var g=a.getUTCMonth()+1,h=a.getUTCDate(),i=a.getUTCFullYear(),j=a.getUTCHours(),k=a.getUTCMinutes(),l=a.getUTCSeconds(),m=a.getUTCMilliseconds();return 10>g&&(g="0"+g),10>h&&(h="0"+h),10>j&&(j="0"+j),10>k&&(k="0"+k),10>l&&(l="0"+l),100>m&&(m="0"+m),10>m&&(m="0"+m),'"'+i+"-"+g+"-"+h+"T"+j+":"+k+":"+l+"."+m+'Z"'}if(b=[],$.isArray(a)){for(c=0;c<a.length;c++)b.push($.toJSON(a[c])||"null");return"["+b.join(",")+"]"}if("object"==typeof a){for(c in a)if(hasOwn.call(a,c)){if(f=typeof c,"number"===f)d='"'+c+'"';else{if("string"!==f)continue;d=$.quoteString(c)}f=typeof a[c],"function"!==f&&"undefined"!==f&&(e=$.toJSON(a[c]),b.push(d+":"+e))}return"{"+b.join(",")+"}"}},$.evalJSON="object"==typeof JSON&&JSON.parse?JSON.parse:function(str){return eval("("+str+")")},$.secureEvalJSON="object"==typeof JSON&&JSON.parse?JSON.parse:function(str){var filtered=str.replace(/\\["\\\/bfnrtu]/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,"");if(/^[\],:{}\s]*$/.test(filtered))return eval("("+str+")");throw new SyntaxError("Error parsing JSON, source is not valid.")},$.quoteString=function(a){return a.match(escape)?'"'+a.replace(escape,function(a){var b=meta[a];return"string"==typeof b?b:(b=a.charCodeAt(),"\\u00"+Math.floor(b/16).toString(16)+(b%16).toString(16))})+'"':'"'+a+'"'}}(jQuery);