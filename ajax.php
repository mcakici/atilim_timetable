<?php @session_start(); error_reporting(1);
$izin = "var";
include_once 'inc.php';
//sleep(2);
##################################################
if($_GET["i"] == "getsubjects"){
##################################################
	
	//	@setcookie("_selectedsubjects",json_encode($selectedsubjects),time()+(3600*24*7),'/');
	
	if(intval($_GET["classid"]) > 0){
		$previewActive = 1;
		$arr_subject = get_lessons("class", $_GET["classid"]);
	}else if(intval($_GET["teacherid"]) > 0){
		$previewActive = 1;
		$arr_subject = get_lessons("teacher", $_GET["teacherid"]);
	}else if(intval($_GET["classroomid"]) > 0){
		$previewActive = 1;
		$arr_subject = get_lessons("classroom", $_GET["classroomid"]);		
	}else if(!empty($_GET["ss"])){
		$previewActive = 1;
		$arr_subject = get_lessons("query",rawurldecode($_GET["ss"]));
		
		if(!empty($_GET["exc"])){
			$_excludelessons = rawurldecode($_GET["exc"]);
		}


	}else{
		$arr_subject = get_lessons();
		if(!isset($_COOKIE["_selectedsubjects"]))
		$selectedsubjects = array();
		else
		$selectedsubjects = json_decode($_COOKIE["_selectedsubjects"],true);
		
		$userselection = 1;
	}
	
	if(isset($_COOKIE["_showintable"])){
		$activeselectviews = json_decode($_COOKIE["_showintable"],true);
	}else{
		$activeselectviews = ["classrooms"];
	}
	

	echo '<table class="table table-bordered table-sm table-striped table-dark noselect">';
	$periods = get_periods();
	echo '<thead class="thead-dark"><tr>';
	echo '<th scope="col"></th>';
	foreach($periods AS $keyper => $per){
		$ttime = date("H:i",time());
		echo '<th scope="col" class="text-center small font-weight-bold '.($ttime >= $per["starttime"] && ((isset($periods[$keyper+1]) && $ttime < $periods[$keyper+1]["starttime"]) || ($ttime < $per["endtime"])) ? 'bg-secondary' : '').'"><span class="textoverflow">'.$per["starttime"].' - '.$per["endtime"].'</span></th>';
	}
	echo '</tr></thead>';
	$days = get_days();
	echo '<tbody>';	
	

	
	foreach($days AS $day){
		echo '<tr scope="col" class="subjectday" data-day="'.$day["id"].'">';
		echo '<th scope="row" class="text-center text-light '.($today_dayid == $day["id"] ? 'bg-secondary' : '').'" style="width: 0.5%; height: 65px; vertical-align:middle;">'.get_eng_shortdayname($day["short"]).'</th>';
		//cells periods
		for($period=1;$period<10;$period++){
			$les = $arr_subject[$day["id"]][$period];
			$totalles = count($les);
			//if(count($arr_subject[$day["id"]]) < 1){ echo '<td></td>'; break; }
			echo '<td class="text-center font-weight-bold2 subjectperiod" style="width: 2%;height:80px;vertical-align: middle;" data-day="'.$day["id"].'" data-period="'.$period.'" data-count="'.$totalles.'"><div style="position:relative;">';
			foreach($les AS $lekey => $leall){
				//check&skip removed ones
				if(checkRemovedSubjects($leall["code"],$leall["section"],$day["id"].$period,($_excludelessons ? $_excludelessons : null))){ if($totalles > 0) $totalles--;  continue; }
				
				//put cards
				$classes = implode(", ",array_map(function($v){ return $v["department"]; },$leall["classes"]));
				$classrooms = implode(", ",array_map(function($v){ return $v["short"]; },$leall["classrooms"]));
				$teachers = implode(", ",array_map(function($v){ return $v["lastname"]; },$leall["teachers"]));
				echo
				'<div class="bg-dark"><a data-toggle="tooltip" data-delay=\'{"show":"600", "hide":"100"}\' data-html="true" title="<small><b>'.htmlspecialchars(mb_convert_case($leall["name"], MB_CASE_UPPER, "UTF-8")).'</b>
				<br>'.$classrooms.'
				<br>'.$classes.'
				<br>'.$teachers.'
				</small>" style="background:'.hex2rgba($leall["color"], 0.2).';padding:5px 0;border:1px solid rgba(255,255,255,0.1);cursor:pointer;position:relative;" onclick="blinksubject(\'.subject'.$leall["code"].($leall["section"] ? $leall["section"] : 0).'\');" data-id="'.$leall["code"].mt_rand().'" data-lcode="'.$leall["code"].'" data-section="'.($leall["section"] ? $leall["section"] : 0).'" class="'.(substr_count($leall["short"]," lab") ? 'itslab' : 'itscourse').' small d-block subjectitem subject'.$leall["code"].($leall["section"] ? $leall["section"] : 0).' '.($lekey > 0 ? 'mt-1' : '').'">'.(substr_count($leall["short"]," lab") ? '<i class="fa fa-flask" aria-hidden="true"></i>' : '').' <b>'.$leall["short"].'</b> 
				'.($userselection ? '<span class="deletebtn" style="position:absolute;right:0;top:0;bottom:0;background:#333;height:100%;width:18px;opacity:0.5;vertical-align:middle;padding-top:5px;" onclick="removesubject(\''.$leall["code"].'\',\''.($leall["section"] ? $leall["section"] : 0).'\');"><i class="fa fa-times"></i></span>' : '').'
				'.(in_array('departments',$activeselectviews) && !empty($classes)  ? '<br><small class="classinfo text-warning textoverflow2">('.$classes.')</small>' : '').'
				'.(in_array('classrooms',$activeselectviews) && !empty($classrooms) ? '<br><small class="classinfo text-warning textoverflow2">('.$classrooms.')</small>' : '').'
				'.(in_array('teachers',$activeselectviews) && !empty($teachers) ? '<br><small class="classinfo text-warning textoverflow2">('.$teachers.')</small>' : '').'
				</a></div>';
				
				$codesection[] = ["code"=>$leall["code"],"section"=>($leall["section"] ? intval($leall["section"]) : 0)];
			}
			if($totalles < 1 && $userselection)
			echo '<div onclick="searchlesson(this);" class="noprint" style="width:100%;height:100%;color:rgba(255,255,255,0.05);font-size:20px;padding:15px;vertical-align:middle;overflow:hidden;cursor:pointer;"><i class="fa fa-search"></i></div>';
			
			
			echo '</div></td>';
			
			//.'<br><small>'.$leall["name"].'</small>
			
		}
		echo '</tr>';
	}
	echo '</tbody></table>';
	
	if($codesection)
	$codesection = array_values(array_map("unserialize", array_unique(array_map("serialize", $codesection))));
	
	if($userselection && $codesection){
		if(isset($_COOKIE["_removedsubjects"])){
			$_removedsubjects = json_decode($_COOKIE["_removedsubjects"],true);
			$_removed_codesection = array_values(array_map("unserialize", array_unique(array_map("serialize", $_removedsubjects))));
			/*foreach($_removedsubjects AS $remsub){
				foreach($remsub["dp"] AS $rs){
					$rsdp = str_split($rs);
					$removedsubjectOut .= $remsub['c'].($remsub['s'] ? '-'.$remsub['s'] : '').' '.get_dayname($rsdp[0]).' '.get_hour($rsdp[1]).'<br>';
				}
				
			}*/
			
		}
		echo "<div class='noprint remsubdiv small text-muted p-2 ml-1' style='position:absolute;'></div>";
		
		echo '<center class="noprint text-muted small">Link for this schedule:
		<input type="text" class="btn btn-dark btn-sm text-secondary copythisone" style="resize: none; width:280px; margin: 5px 0;" readonly="readonly" value="'.$_siteurl_full.'?ss='.rawurlencode(json_encode($codesection)).($_removed_codesection ? '&exc='.rawurlencode(json_encode($_removed_codesection)) : '').'" /></center>';
		//echo '<div style="position:absolute;right:10px;" class="text-muted noprint small"><a class="text-info" href="'.$_siteurl.'?ss='.rawurlencode(json_encode($codesection)).'" target="_blank">Link</a></div>';
	}

	if($previewActive && $codesection){
		echo '<script>data = \''.addslashes(json_encode($codesection)).'\'; exclude = \''.addslashes($_excludelessons).'\';</script>';
		echo '<button class="btn btn-sm btn-block btn-secondary btnedit noprint mb-1 rounded-0" onclick="useNewData();">Edit this schedule (All lessons preselected will be deleted!)</button>';
	}
	
##################################################
}else if($_GET["i"] == "showexamhours"){
##################################################	
	$selected_departments = $_POST["dep"];
	if(isset($_POST["dep"])){
		
	}else{
		echo '<div class="alert m-2 alert-danger">Select departments!</div>';
		die();
	}
	$lessonStat = get_lessoncount($selected_departments);
	
	echo '<table class="table table-bordered table-sm table-striped table-dark noselect">';
	$periods = get_periods();
	echo '<thead class="thead-dark"><tr>';
	echo '<th scope="col"></th>';
	foreach($periods AS $per){
		$totalLessonForThatPeriod = 0;
		foreach($lessonStat["result"] AS $k => $v){
			$k = (string)$k;
			if($per["period"] == $k[1])
			$totalLessonForThatPeriod += $v;
		}

		echo '<th scope="col" class="text-center small font-weight-bold"><span class="textoverflow">'.$per["starttime"].' - '.$per["endtime"].'</span><small class="text-warning printblack"><br>Period: '.$per["period"].', Total: '.$totalLessonForThatPeriod.'</small></th>';
	}
	echo '</tr></thead>';
	
	$days = get_days();
	echo '<tbody>';
	
	$avg = mean($lessonStat["result"]); 
	$stddev =  std($lessonStat["result"]);
	
	echo '<h4 class="text-light ml-2 mb-0 mt-2 printblack">Possibility of taking the exam according to the course hours</h4>';
	
	echo "<div class='p-2 text-light printblack'> Number of Lessons: <b>".$lessonStat["total"]."</b>, Standart Deviation: <b>$stddev</b>, Mean: <b>$avg</b></div>";
	
	foreach($days AS $day){
		echo '<tr scope="col" class="subjectday" data-day="'.$day["id"].'">';
		$totalLessonForThatDay = 0;
		foreach($lessonStat["result"] AS $k => $v){
			$k = (string)$k;
			if($day["id"] == $k[0])
			$totalLessonForThatDay += $v;
		}
		echo '<th scope="row" class="text-center text-light" style="width: 0.5%; height: 65px; vertical-align:middle;">'.get_eng_shortdayname($day["short"]).'<br><small class="small text-warning printblack">Day '.$day['id'].'<br>('.$totalLessonForThatDay.')</small></th>';
		//cells periods
		for($period=1;$period<10;$period++){
				
			$dp = $day["id"].$period;
			$zscore = ($lessonStat["result"][$dp] - $avg) / $stddev;
			
			$orjPercentage = ((cdf($zscore) * 100));
			$percentage = number_format(100 - (cdf($zscore) * 100),2);
			//echo '<br>SS:'.$stddev.';  MEAN:'.$avg.'; ZSCORE:'.$zscore.'</br>';
			
			
			
			
			//$les = $arr_subject[$day["id"]][$period];
			//if(count($arr_subject[$day["id"]]) < 1){ echo '<td></td>'; break; }
			echo '<td class="text-center font-weight-bold2 subjectperiod" style="width: 2%;height:80px;vertical-align: middle; background-color:'.getColor($orjPercentage/100).'" data-day="'.$day["id"].'" data-period="'.$period.'" data-count="0"><div style="position:relative;">';
			echo "<small>Lessons:</small> <b>".$lessonStat["result"][$dp]."</b><br>";
			echo '<small class="mt-2 d-inline-block">Probability of Participation</small><b><br>'.$percentage.'</b> %<br>';
			echo '<div class="small noprint text-warning" style="position:absolute;bottom:-10px;right:0;"><small><abbr title="'.$zscore.'">Z</abbr></small></div>';
			
			
			echo '</div></td>';
			
			//.'<br><small>'.$leall["name"].'</small>
			
		}
		echo '</tr>';
	}
	
	
	echo '</tbody></table>';
	
	$classes = get_classes("fromArrayAll",$selected_departments);
	$classNames = array_map(function($d){ return $d["name"]; }, $classes);
	echo "<div class='p-2 text-light table-dark printblack small'><b>Selected:</b> ".implode(", ",$classNames)."</div>";
	
##################################################
}else if($_GET["i"] == "searchlessons"){
##################################################
	if(isset($_GET["day"]) && isset($_GET["period"]) && isset($_GET["maxperiod"])) $slvalues = ["day" => $_GET["day"], "period" => $_GET["period"], "duration" => $_GET["maxperiod"], "dp" => $_GET["day"].''.$_GET["period"]];
	
	$searchedlessons = get_subjects("searchSubjects", ($slvalues ? $slvalues : null));
	
	
	//$searchedlessons = reset($searchedlessons);
	//var_dump($searchedlessons);
	if(isset($_COOKIE["_department"]))
	$activedepartmans = json_decode($_COOKIE["_department"],true);
	
	echo '
	<div class="btn-group mb-1 rounded-0 d-flex w-100" role="group" aria-label="Basic example">
		<button type="button" class="btn btn-sm btn-dark rounded-0 w-100" onclick="get_subjectlist(\'&\');"><i class="fa fa-th-list" aria-hidden="true"></i> All courses</button>
		<button type="button" class="btn btn-sm btn-dark rounded-0 w-100" onclick="get_subjectlist();"><i class="fa fa-refresh" aria-hidden="true"></i> Refresh list</button>
	</div>';
	
	/*
	if(count($activedepartmans))
	echo '<div class="text-white p-2 bg-dark mb-1 textoverflow" style="line-height:1.6;"><i class="fa fa-filter" aria-hidden="true"></i> <b>Active Filtered Departments</b><br> '.(count($activedepartmans) ? implode(', ',$activedepartmans) : 'All').'</div>';
	*/
	
	
	
	if($slvalues)
	echo '<div class="text-white p-2 bg-dark mb-1 textoverflow" style="line-height:1.6;"><i class="fa fa-search" aria-hidden="true"></i> <b>Searching range</b><br> Day <span class="text-warning font-weight-bold">'.get_dayname($slvalues["day"]).'</span>,
	 <span class="text-warning font-weight-bold">'.($slvalues["period"]).'.</span> Hour</div>';
	
	if($_COOKIE["_preventconflict"] == 1)
	echo '<div class="text-warning font-weight-bold small p-2 bg-dark mb-2" style="line-height:1.6;">> Overlapping courses have been removed!</div>';

	
	if(!$searchedlessons) die('<div class="text-white p-2" style="background:rgba(255,255,255,0.1);line-height:1.8;">No courses found!</div>');
																															   
	foreach($searchedlessons AS $sub){
		//foreach($slessons AS $sub){
			echo '<li><a class="textoverflow" href="javascript:void(0)" onclick="addsubject(\''.$sub["code"].'\','.($sub["section"] ? "'".$sub["section"]."'" : 0).',this);" style="background:'.hex2rgba($sub["color"], 0.2).';color:#fff;">'.$sub["short"].' - '.$sub["name"].'</a></li>';
		//}
	}
##################################################
##################################################
}else if($_GET["i"] == "bologna"){
##################################################
	$newpageurl = get_bologna($_GET["lcode"]);
	if(!empty($newpageurl)) echo $newpageurl;
	die();
##################################################
}
?>