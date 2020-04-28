<?php ini_set('max_execution_time', 100); set_time_limit(100);
$izin = "var";  include_once 'db.php';
$_page_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$currentTime = date("H:i:s",time());
$today_dayid = intval(date("w",time()));		

if(@$_SERVER["HTTP_HOST"] == "localhost"){
	 $_protocol = "http:";
	 $_siteurl = "http://localhost/ati/";
	 $_siteurl_full = "http://localhost/ati/";
	 $_local = 1;
}else{
    if($_SERVER["SERVER_PORT"] != 443){ 
	 $_protocol = "http:";
    }else{
	 $_protocol = "https:";
    }
    $_siteurl = "//atilim.laplup.com/";
    $_siteurl_full = "https://atilim.laplup.com/";
}

function getColor($value){
    //value from 0 to 1
    $hue=((1-$value)*120);
    return "hsla($hue,100%,50%,0.25)";
}

function checkRemovedSubjects($code,$sec,$dp,$data = null){
	if($data !== null){
		$_removedsubjects = json_decode($data,true);
	}else	if(isset($_COOKIE["_removedsubjects"])){
		$_removedsubjects = json_decode($_COOKIE["_removedsubjects"],true);
	}else{
		return false;
	}
	
	foreach($_removedsubjects AS $rikey => $removeditem){
		if($removeditem["c"] == $code && $removeditem["s"] == $sec){
			foreach($removeditem["dp"] AS $dpitem){
				if($dpitem == $dp) return true;
			}
		}
		
	}
	return false;
}

/* Standard Deviation */
function std($arr){
	$mean = mean($arr);
	$sum = 0;
	foreach($arr AS $val){
		$sum += ($val - $mean)*($val - $mean);
	}
	return sqrt($sum/(count($arr)-1));	
}
/* Mean */
function mean($input_array){
  $total = 0;
  foreach ($input_array as $value)
  {
    $total += $value;
  }
  return ($total / count($input_array));
}

function erf($x){
    $pi = 3.1415927;
    $a = (8*($pi - 3))/(3*$pi*(4 - $pi));
    $x2 = $x * $x;

    $ax2 = $a * $x2;
    $num = (4/$pi) + $ax2;
    $denom = 1 + $ax2;

    $inner = (-$x2)*$num/$denom;
    $erf2 = 1 - exp($inner);

    return sqrt($erf2);
}

function cdf($n){
    if($n < 0){
      return (1 - erf($n / sqrt(2)))/2;
    }else{
      return (1 + erf($n / sqrt(2)))/2;
    }
}

function get_dayname($value){
	switch($value){
		case 1: $out = "Monday"; break;
		case 2: $out = "Tuesday"; break;
		case 3: $out = "Wednesday"; break;
		case 4: $out = "Thursday"; break;
		case 5: $out = "Friday"; break;
		case 6: $out = "Saturday"; break;
		case 7: $out = "Sunday"; break;
	}
	return $out;
}

function get_hour($value){
	switch($value){
		case 1: $out = "09:30 - 10:20"; break;
		case 2: $out = "10:30 - 11:20"; break;
		case 3: $out = "11:30 - 12:20"; break;
		case 4: $out = "12:30 - 13:20"; break;
		case 5: $out = "13:30 - 14:20"; break;
		case 6: $out = "14:30 - 15:20"; break;
		case 7: $out = "15:30 - 16:20"; break;
		case 8: $out = "16:30 - 17:20"; break;
		case 9: $out = "17:30 - 18:20"; break;
	}
	return $out;
}

function get_eng_shortdayname($value){
	switch($value){
		case "Pa": $out = "Mon"; break;
		case "Sa": $out = "Tue"; break;
		case "Ça": $out = "Wed"; break;
		case "Pe": $out = "Thu"; break;
		case "Cu": $out = "Fri"; break;
		case "Ct": $out = "Sat"; break;
		case "Pz": $out = "Sun"; break;
	}
	return $out;
}

function get_periods(){
	global $db;

	$query = $db->query("SELECT periods.* FROM periods");
	$result = array();
	while ($row_query = $query->fetch_object()) {
		$result[] = array(
			"id" => $row_query->id,
			"name" => $row_query->name,
			"short" => $row_query->short,
			"starttime" => $row_query->starttime,
			"endtime" => $row_query->endtime,
			"period" => $row_query->period
			);
	}
	if(count($result))
	return $result;
	else
	return false;
}

function get_days(){
	global $db;
	$query = $db->query("SELECT days.* FROM days WHERE id < 6");
	$result = array();
	while ($row_query = $query->fetch_object()) {
		$result[] = array(
			"id" => $row_query->id,
			"name" => $row_query->name,
			"short" => $row_query->short,
			"type" => $row_query->type,
			"vals" => $row_query->vals,
			"val" => $row_query->val
			);
	}
	if(count($result))
	return $result;
	else
	return false;
}


function get_classes($type = null,$value = null){
	global $db;
	$_query_extra = $_query_group = $_query_select = "";
	
	if($type == "department"){
		$_query_select = ",GROUP_CONCAT(DISTINCT id SEPARATOR ',') AS departmentids";
		$_query_extra = "";
		$_query_group = " GROUP BY department ";
	}else if($type == "fromArray"){
		if(!is_array($value)) return false;
		$_query_extra = " classes.id IN(".mysqli_real_escape_string($db,implode(",",$value)).") ";
		$_query_group = " GROUP BY department ";
	}else if($type == "fromArrayAll"){
		if(!is_array($value)) return false;
		$_query_extra = " classes.id IN(".mysqli_real_escape_string($db,implode(",",$value)).") ";
		
	}else if($type == "getdepartmentids"){
		//$_query_select = ",GROUP_CONCAT(DISTINCT id SEPARATOR ',') AS departmentids";
		if(empty($value)) return false;
		
		$parsednames = json_decode($value,true);
		if(count($parsednames)){
			$departnames = array_map(function($v){ return "'".str_value($v)."'"; },$parsednames);
			$_query_extra = " department IN(".implode(",",$departnames).") ";
		}
	}
	
	$query = $db->query("SELECT classes.* $_query_select FROM classes ".($_query_extra ? " WHERE ".$_query_extra : '')." $_query_group ORDER BY name ASC");
	$result = array();
	while ($row_query = $query->fetch_object()) {
		$result[] = array(
			"id" => $row_query->id,
			"name" => $row_query->name,
			"short" => $row_query->short,
			"department" => $row_query->department,
			"departmentids" => ($row_query->departmentids ? $row_query->departmentids : null),
			"color" => $row_query->color
			);
	}
	if(count($result))
	return $result;
	else
	return false;
}

function get_subjectlist(){
	global $db;
	//if(!$classroomids || !is_array($classroomids)) return false;
	$query = $db->query("SELECT code, name FROM subjects GROUP BY code ORDER BY code ASC");
	$result = array();
	while ($row_query = $query->fetch_object()) {
		$result[] = array(
			"code" => $row_query->code,
			"name" => $row_query->name
			);
	}
	if(count($result))
	return $result;
	else
	return false;
}

function get_classroom($classroomids = null){
	global $db;
	if(!$classroomids || !is_array($classroomids))
	$query = $db->query("SELECT classrooms.* FROM classrooms");
	else
	$query = $db->query("SELECT classrooms.* FROM classrooms WHERE id IN(".mysqli_real_escape_string($db,implode(",",$classroomids)).")");
	
	$result = array();
	while ($row_query = $query->fetch_object()) {
		$result[] = array(
			"id" => $row_query->id,
			"name" => $row_query->name,
			"short" => $row_query->short,
			"color" => $row_query->color
			);
	}
	if(count($result))
	return $result;
	else
	return false;
}

function get_teachers($teacherids = null){
	global $db;
	$_query_extra = "";
	if($teacherids !== null){
		$_query_extra = " AND id IN(".mysqli_real_escape_string($db,implode(",",$teacherids)).") ";
	}
	$query = $db->query("SELECT teachers.* FROM teachers WHERE 1=1 $_query_extra");
	$result = array();
	while ($row_query = $query->fetch_object()) {
		$result[] = array(
			"id" => $row_query->id,
			"lastname" => $row_query->lastname,
			"short" => $row_query->short,
			"color" => $row_query->color
			);
	}
	if(count($result))
	return $result;
	else
	return false;
}
//SELECT * FROM `lessons` WHERE JSON_CONTAINS(classids, "1")
/*LEFT JOIN classrooms ON JSON_CONTAINS(cards.classroomids, CAST(classrooms.id AS CHAR))*/

//var_dump(get_nonconflict_subjects()); die();
function get_subjects($type = null, $value = null){
	global $db,$_COOKIE;
	$_query_extra = $_query_select = "";
	$_extra_orderby = "GROUP BY code,section";
	$_query_extra = " WHERE 1=1 ";
	
	##ÇAKIŞMA ÖNLE
	if($_COOKIE["_preventconflict"] == 1){
		## Seçilmiş dersler
		if(isset($_COOKIE["_usedPeriods"]))
		$_usedPeriods = @json_decode($_COOKIE["_usedPeriods"],true);
		
		if(count($_usedPeriods) > 0){
			$dps = array_map(function($v){ return intval($v['dp']); }, $_usedPeriods);
			//var_dump($dps);
			$_query_extra .= " AND (subjects.code,subjects.section) NOT IN(SELECT code,section FROM usedperiods WHERE dp IN(".implode(",", $dps).") )";		
		}
	}

	##departman seçimi
	if(isset($_COOKIE["_department"])){
		$classinfo = get_classes("getdepartmentids",$_COOKIE["_department"]);
		if($classinfo && count($classinfo) > 0){
			
			//required mysql 5.7.8 above for json functions
			$finalids = array_map(function($d){ if($d["id"] > 0) return "JSON_CONTAINS(lessons.classids, '".intval($d["id"])."')"; }, $classinfo);
			
			//old verison mysql
			//$finalids = array_map(function($d){ return " (lessons.classids LIKE '[".($d["id"])."]' OR lessons.classids LIKE '[".($d["id"]).",%' OR lessons.classids LIKE '%,".($d["id"]).",%' OR lessons.classids LIKE '%,".($d["id"])."]') "; }, $classinfo);
			$_query_extra .= " AND (".implode(" OR ",$finalids).")";
			
			
			
			//echo $_query_extra;
			//$_extra_orderby = ' GROUP BY lessons.subjectid, subjects.code, subjects.section, cards.lessonid ';
		}
	}
	
	if($type == "searchSubjects"){
		if(is_array($value)){
			/*
			############################################################
			if(count($_POST["postdata"])){
				foreach($_POST["postdata"] AS $key => $eper){
					$dp[] = $eper["dp"];
				}
				$_query_extra .= " AND (subjects.code,subjects.section) NOT IN(SELECT code,section FROM usedperiods WHERE dp IN(".implode(",", $dp).") )";				
			}
			############################################################
			$_query_select .= "";
			*/
			//$_query_extra .= ' AND days.id = '.int_value($value["day"]).' AND cards.period = '.int_value($value["period"]).' AND lessons.duration <= '.int_value($value["duration"]).' ';
			$_query_extra .= " AND (subjects.code,subjects.section) IN(SELECT code,section FROM usedperiods WHERE dp = ".mysqli_real_escape_string($db,$value["dp"]).") ";
			
			//$_extra_orderby = ' GROUP BY lessons.subjectid, subjects.code, subjects.section, cards.lessonid ';
			//$_extra_orderby = ' GROUP BY subjects.code, subjects.section ';
			
			
		}
	}else{
		
	}
	

	
	$query = $db->query("SELECT subjects.* $_query_select FROM subjects
						INNER JOIN lessons ON lessons.subjectid = subjects.id
						INNER JOIN cards ON cards.lessonid = lessons.id
						INNER JOIN days ON days.vals = cards.days
						$_query_extra
						$_extra_orderby");

	$result = array();
	while ($row_query = $query->fetch_object()) {
		$result[] = array(
			"id" => $row_query->id,
			"code" => $row_query->code,
			"name" => str_replace("lab","",$row_query->name),
			"short" => str_replace("lab","",$row_query->short),
			"section" => $row_query->section,
			"color" => $row_query->color
			);
	}
	if(count($result))
	return $result;
	else
	return false;
}

function get_lessoncount($selected_departments = null){
	global $db;
	
	if($selected_departments !== null && is_array($selected_departments)){
		$finalClassids = array_map(function($d){ if(intval($d) > 0) return "JSON_CONTAINS(usedperiods.classids, '".intval($d)."')"; }, $selected_departments);
		$_query_extra = '						
						WHERE usedperiods.dayid < 6  AND  ('.implode(" OR ",$finalClassids).')
						GROUP BY usedperiods.dp
						';
	}else{
		$_extra_query = " WHERE usedperiods.dayid < 6 GROUP BY usedperiods.dp ";
	}
	
	$result = array();
	$total = 0;
	$query = $db->query("SELECT usedperiods.dp,COUNT(usedperiods.id) AS total FROM usedperiods $_query_extra ");
	while ($row_query = $query->fetch_object()) {
		$result[$row_query->dp] = intval($row_query->total);
		$total += $row_query->total;
	}
	
	for($x=1;$x<6;$x++){
		for($y=1;$y<10;$y++){
			if(!isset($result[$x.$y])) $result[$x.$y] = 0;
		}
	}
	
	if(count($result))
	return array("total"=> $total,"result"=>$result);
	else
	return false;
}

function get_lessons($type = null, $value = null){
	global $db;
	//if(!$selected || !is_array($selected)) return false;
	
	$_extra_query = $_extra_join = $_extra_orderby = "";
	$_extra_query = " 1=1 ";
	
	/*
	if(isset($_COOKIE["_department"])){
		$classinfo = get_classes("getdepartmentids",$_COOKIE["_department"]);
		if($classinfo){
			$finalids = array_map(function($d){ if($d["id"] > 0) return "JSON_CONTAINS(lessons.classids, '".($d["id"])."')"; }, $classinfo);
			$_extra_query .= " AND (".implode(" OR ",$finalids).")";
		}
	}
	*/
	
	if($type == "class" && intval($value)){
		//mysql 5.7.8 üzeri
		$_extra_query .= " AND JSON_CONTAINS(lessons.classids, '".mysqli_real_escape_string($db,intval($value))."') ";
		
		//old style mysql query
		//$_extra_query .= " AND (lessons.classids LIKE '[".int_value($value)."]' OR lessons.classids LIKE '[".int_value($value).",%' OR lessons.classids LIKE '%,".int_value($value).",%' OR lessons.classids LIKE '%,".int_value($value)."]') ";
		
	}elseif($type == "teacher" && intval($value)){
		$_extra_query .= " AND (lessons.teacherids LIKE '[".int_value($value)."]' OR lessons.teacherids LIKE '[".int_value($value).",%' OR lessons.teacherids LIKE '%,".int_value($value).",%' OR lessons.teacherids LIKE '%,".int_value($value)."]') ";
		
	}elseif($type == "classroom" && intval($value)){
		$_extra_query .= " AND (lessons.classroomids LIKE '[".int_value($value)."]' OR lessons.classroomids LIKE '[".int_value($value).",%' OR lessons.classroomids LIKE '%,".int_value($value).",%' OR lessons.classroomids LIKE '%,".int_value($value)."]') ";
		
	}elseif($type == "query" && !empty($value)){
		$selected = @json_decode($value,true);
		if(count($selected) > 0){
			$selected = array_map(function($v){ global $db;
				return " (subjects.code='".mysqli_real_escape_string($db,$v['code'])."' AND subjects.section='".($v['section'] ? mysqli_real_escape_string($db,$v['section']) : '0')."') ";
			}, $selected);
			$_extra_query .= " AND (".implode(" OR ", $selected).")";
		}else{
			return false;
		}
		
	}elseif($type == "department"){

		//$_extra_query .= " JSON_CONTAINS(lessons.classids, (SELECT id FROM classes WHERE department = '".mysqli_real_escape_string($db,($value))."')) ";
		
	}elseif($type == "searchlesson"){
		if(is_array($value)){
		$_extra_query .= ' AND days.id = '.int_value($value["day"]).' AND cards.period = '.int_value($value["period"]).' AND lessons.duration <= '.int_value($value["duration"]).' ';
		$_extra_orderby = ' GROUP BY lessons.subjectid, subjects.code, subjects.section, cards.lessonid ';
		}
		
	}else{
		if(isset($_COOKIE["_selectedsubjects"]))
		$selected = @json_decode($_COOKIE["_selectedsubjects"],true);
		//var_dump($selected);
		if(count($selected) > 0){
			$selected = array_map(function($v){ global $db;
				return " (subjects.code='".mysqli_real_escape_string($db,$v['code'])."' AND subjects.section='".($v['section'] ? mysqli_real_escape_string($db,$v['section']) : '0')."') ";
			}, $selected);
			$_extra_query .= " AND (".implode(" OR ", $selected).")";
		}else{
			return false;
		}
		
	}
	

	//echo $_extra_query; die();

	$query_subject = $db->query("
		SELECT subjects.*,cards.period,periods.starttime,periods.endtime,cards.days,cards.weeks,cards.classroomids,lessons.duration,days.name AS dayname,days.id AS dayid, subjects.color
		,lessons.teacherids,lessons.classids
		FROM lessons
		
		INNER JOIN subjects ON lessons.subjectid = subjects.id
		INNER JOIN cards ON cards.lessonid = lessons.id
		INNER JOIN periods ON cards.period = periods.period
		INNER JOIN days ON days.vals = cards.days
		
		".($_extra_query ? 'WHERE '.$_extra_query : '')."
		
		".$_extra_orderby."
		");
	
		
	$arr_subject= array();
	while ($row_sub = $query_subject->fetch_object()) {
		if($type == "searchlesson") $durationadded = $row_sub->duration-1; else $durationadded = 0;
		while($durationadded < $row_sub->duration){
			$arr_subject[$row_sub->dayid][$row_sub->period+$durationadded][] = array(
				"id" => $row_sub->id,
				"name" => $row_sub->name,
				"short" => $row_sub->short,
				"section" => $row_sub->section,
				"color" => $row_sub->color,
				"code" => $row_sub->code,
				
				"duration" => (int)$row_sub->duration,
				"classes" => ($row_sub->classids ? get_classes("fromArray",json_decode($row_sub->classids,true)) : null),
				"classrooms" => ($row_sub->classroomids ? get_classroom(json_decode($row_sub->classroomids,true)) : null),
				"teachers" => ($row_sub->teacherids ? get_teachers(json_decode($row_sub->teacherids,true)) : null),
				"days" => $row_sub->days,
				"dayid" => $row_sub->dayid,
				"dayname" => $row_sub->dayname,
				"period" => $row_sub->period,
				"starttime" => $row_sub->starttime,
				"endtime" => $row_sub->endtime,
			);
			$durationadded++;
		}
	}
	
	if(count($arr_subject))
	return $arr_subject;
	else
	return false;
}



function sanitize_output($buffer) {
    $search = array(
        '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
        '/[^\S ]+\</s',  // strip whitespaces before tags, except space
        '/(\s)+/s'       // shorten multiple whitespace sequences
    );
    $replace = array(
        '>',
        '<',
        '\\1'
    );
    $buffer = preg_replace($search, $replace, $buffer);
    return $buffer;
}

function int_value($var){
	global $db;
	return trim(strip_tags(mysqli_real_escape_string($db,abs(intval($var)))));
}

function str_value($var){
	global $db;
	return trim(strip_tags(mysqli_real_escape_string($db,urldecode($var))));
}

function get_bologna($code){
	global $db;
	if(empty($code)) return;
	$get_bologna = $db->query("SELECT bologna FROM subjects WHERE code='".str_value($code)."' LIMIT 1")->fetch_object()->bologna;
	if($get_bologna == null){
		$get_bologna = bot_curl('https://www.atilim.edu.tr/get-lesson-ects/'.$code);
		if(!empty($get_bologna)) $db->query("UPDATE subjects SET bologna='".mysqli_real_escape_string($db,$get_bologna)."' WHERE code='".str_value($code)."' ");
	}
	return $get_bologna;
}

function getir($baslangic, $son, $cekilmek_istenen) {
	preg_match_all('/' . preg_quote($baslangic, '/') .'(.*?)'. preg_quote($son, '/').'/is', $cekilmek_istenen, $m);
	return $m[1];
}
function bot_curl($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36");
	curl_setopt($ch, CURLOPT_ENCODING ,"UTF-8");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10000);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	$data = curl_exec($ch);
	return $data;
}

function hex2rgba($color, $opacity = false) {
 
	$default = 'rgb(0,0,0)';
 
	//Return default if no color provided
	if(empty($color))
          return $default; 
 
	//Sanitize $color if "#" is provided 
        if ($color[0] == '#' ) {
        	$color = substr( $color, 1 );
        }
 
        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }
 
        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);
 
        //Check if opacity is set(rgba or rgb)
        if($opacity){
        	if(abs($opacity) > 1)
        		$opacity = 1.0;
        	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
        	$output = 'rgb('.implode(",",$rgb).')';
        }
 
        //Return rgb(a) color string
        return $output;
}

?>