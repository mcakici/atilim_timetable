<?php @session_start(); @header("Content-Type: text/html; charset=utf-8"); error_reporting(1);

$izin = "var";
include_once 'inc.php';

if(!isset($_COOKIE["_showintable"])){
	$activeselectviews = ["classrooms"];
	@setcookie("_showintable",json_encode($activeselectviews),time()+(3600*24*7),'/');
}
//ob_start("sanitize_output");
?><!doctype html>
<html>
<head>
	<title>Schedule Planner - Atılım Engineering Faculty</title>
	<meta name="viewport" content="width=1150px" />
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="robots" content="noindex, nofollow">
	<meta name="referrer" content="no-referrer">
	<meta name="referrer" content="never">
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/bootstrap-select.min.css">
	<link rel="stylesheet" href="css/style.css?v2">
	<script src="js/jquery-3.3.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap-select.min.js"></script>
	<script src="js/main.js?v3"></script>
</head>
<body data-qstring="<?="&".($_SERVER['QUERY_STRING'] ? addslashes($_SERVER['QUERY_STRING']) : '').""?>">
<div class="strip noprint"></div>
<div class="container-fluid p-0">
<div class="row2">
	<div>
	
<?

/*
echo '<pre>';
//print_r($arr_subject);
echo '</pre>';
*/



?>
<div class="d-flex" style="position:relative;">
	
	<div class="leftsidebar" style="width:250px;min-width:250px;position:fixed;left:0;top:0;height:100%;overflow:hidden;overflow-x:hidden;height:100%;background:#333;z-index:2;box-shadow: 0px 0px 10px black; ">

		<input style="border-radius:0 !important;" type="text" class="clearfix" id="quicksearchinput" onkeyup="quick_search()" placeholder="Filter lessons..">
		<div class="clearfix"></div>
		<select class="selectpicker selectDepartment show-tick mr-1" data-width="100%" data-size="20" data-style="btn-dark btn-sm rounded-0 shadow-none" data-actions-box="true" title="-- Filter Departments --" multiple>
				<?
				if(isset($_COOKIE["_department"])) $activedepartmans = json_decode($_COOKIE["_department"],true);
				$departments = get_classes("department");
				//echo '<option value="all" selected>-- Hepsi --</option>';
				foreach($departments AS $dep){
					echo '<option value="'.$dep['department'].'" '.(in_array($dep['department'],$activedepartmans) ? 'selected' : '').'>'.$dep['department'].'</option>';
				}
				?>
		</select>
		
			
			<div class="btn btn-sm btn-dark w-100 rounded-0" style="padding:0; outline:none; box-shadow:none; background:#27282E; border-color:#1d2124;">
				<div class="customdtcheck checkboxes">
					<div class="check" data-toggle="tooltip" data-html="true" title="<small>The lessons that overlap with the lesson hours of the lesson in the current table are removed from the list.</small>">
						<input id="preventconflict" class="checkboxpreventconflict" value="1" type="checkbox" <?=($_COOKIE["_preventconflict"] == 1? 'checked' : '')?>>
						<label for="preventconflict" class="small mr-1" style="padding:3px 3px; color:#fff;"><div class="box sm mr-1"><i class="fa fa-check"></i></div>&nbsp;Hide overlapping lessons in the list&nbsp;</label>
					</div>
				</div>
			</div>
		
		
		<? $subjectlist = get_subjects();
		echo '<ul id="subjectlist" style="height:calc(100% - 98px);margin-top:2px;width:100%;overflow-y:auto;"></ul>'; ?>
	</div>
	
	<div class="w-100 h-100 maincontainer" style="margin-left:250px;overflow:auto;overflow-x:hidden;height:100%;min-height:650px;">
		<div class="barheader text-light pl-2 pt-1" style="width:100%;height:45px;background:rgba(0,0,0,0.3);position:relative;">
			<?					 
			$active_lessons = $db->query("SELECT COUNT(usedperiods.id) AS total FROM usedperiods INNER JOIN periods ON periods.period = usedperiods.period
																	 WHERE usedperiods.dayid = ".$today_dayid."
																	 AND TIME('$currentTime') BETWEEN TIME(periods.starttime) AND TIME(periods.endtime) + INTERVAL 10 MINUTE
																	 LIMIT 1")->fetch_object()->total;			
			
			?>
			<h4 class="d-inline-block ml-2 mt-1" style="padding-left:40px;">Atılım Schedule Planner<small> v1.1 <a class="text-muted ml-1" href="javascript:void(0);" title="Current schedule & refresh" onclick="refresh_table();"><i class="fa fa-home" aria-hidden="true"></i></a><span class="small-text font-weight-normal badge badge-pill badge-default"><small>Active lessons:</small> <i class='text-info'><?=$active_lessons?></i></span></small></h4>
			<div class="loader" style="display:none;"></div>
			<div class="float-right" style="margin-top:3px;">
			<select class="selectpicker selectClass show-tick2 mr-1"  data-live-search="true" data-width="140px" data-size="20" data-style="btn-dark btn-sm" title="-- Current Layouts --">
				<?
				$classes = get_classes();
				foreach($classes AS $class){
					echo '<option value="'.$class['id'].'">'.$class['short'].'</option>';
				}
				?>
			</select>
			<select class="selectpicker selectClassroom show-tick2 mr-2"  data-live-search="true" data-width="140px" data-size="20" data-style="btn-dark btn-sm" title="-- Classrooms --">
				<?
				$classrooms = get_classroom();
				//var_dump($teachers);
				foreach($classrooms AS $classroom){
					echo '<option value="'.$classroom['id'].'">'.$classroom['short'].'</option>';
				}
				?>
			</select>
			<select class="selectpicker selectTeacher show-tick2 mr-2"  data-live-search="true" data-width="140px" data-size="20" data-style="btn-dark btn-sm" title="-- Teachers --">
				<?
				$teachers = get_teachers();
				//var_dump($teachers);
				foreach($teachers AS $teacher){
					echo '<option value="'.$teacher['id'].'">'.$teacher['lastname'].'</option>';
				}
				?>
			</select>
			</div>
		</div>
		<div class="barheader text-light pl-2 pt-2 pr-2" style="height:48px;background: #262833; border-bottom:2px solid #333;">
			
			<form class="d-flex float-left small" style="position:Relative;" onsubmit="showExamhours(this);return false;">
				<div class="d-inline-block mr-2 text-right">Best Hours<br>For Exam:</div>
				<div class="btn-group" role="group">
							<select name="dep[]" class="selectpicker selectClassForExam show-tick2" data-actions-box="true" data-live-search="true" data-width="160px" data-size="20" data-style="btn-dark btn-sm" title="-- Departments --" multiple>
								<?
								$classes = get_classes();
								foreach($classes AS $class){
									echo '<option value="'.$class['id'].'">'.$class['short'].'</option>';
								}
								?>
							</select>
							<button type="submit" class="btn btn-sm btn-secondary mr-2"><i class="fa fa-arrow-right" aria-hidden="true"></i></button>
				</div>
				
			</form>
			<button class="btn btn-sm btn-secondary d-none mr-2"><i class="fa fa-paint-brush" aria-hidden="true"></i> Empty Classrooms</button>
			<? /*
			<select class="selectpicker selectDepartment show-tick mr-1" data-width="220px" data-size="20" data-style="btn-dark btn-sm" data-actions-box="true" title="-- Filter Departments --" multiple>
				<?
				if(isset($_COOKIE["_department"])) $activedepartmans = json_decode($_COOKIE["_department"],true);
				$departments = get_classes("department");
				//echo '<option value="all" selected>-- Hepsi --</option>';
				foreach($departments AS $dep){
					echo '<option value="'.$dep['department'].'" '.(in_array($dep['department'],$activedepartmans) ? 'selected' : '').'>'.$dep['department'].'</option>';
				}
				?>
			</select>
			*/?>
			
			<? /*
			<select class="selectpicker selectSubjects show-tick mr-1" data-live-search="true" data-width="220px" data-size="20" data-style="btn-dark btn-sm" data-actions-box="true" title="-- Ders seçimi --" multiple>
				<?
				$allsubjects = get_subjectlist();
				//echo '<option value="all" selected>-- Hepsi --</option>';
				foreach($allsubjects AS $sone){
					echo '<option value="'.$sone['code'].'" '.(in_array($sone['code'],$activedepartmans) ? 'selected' : '').'>'.$sone['code'].'</option>';
				}
				?>
			</select>
			<button class="btn btn-sm btn-dark">Generate</button>
			<?
			
			<div class="btn btn-sm btn-dark mr-1" style="padding:0; outline:none; box-shadow:none; background:#27282E; border-color:#1d2124;">
				<div class="customdtcheck checkboxes">
					<div class="check" data-toggle="tooltip" data-html="true" title="<small>Lessons that do not overlap with the course hours of the courses in the current table will be listed in the left section.</small>">
						<input id="preventconflict" class="checkboxpreventconflict" value="1" type="checkbox" <?=($_COOKIE["_preventconflict"] == 1? 'checked' : '')?>>
						<label for="preventconflict" class="small mr-1" style="padding:3px 3px; color:#fff;"><div class="box mr-1"><i class="fa fa-check"></i></div>&nbsp;Prevent Overlapping&nbsp;</label>
					</div>
				</div>
			</div>
			
			
			
			*/?>
			
			
			
			
			

			
			

			<button onclick="removeSelectedSubjectCookies();" class="btn btn-sm btn-primary pull-right mr-2"><i class="fa fa-paint-brush" aria-hidden="true"></i> Clear</button>
			<button onclick="window.print();" class="btn btn-sm btn-secondary pull-right mr-2"><i class="fa fa-print"></i> Print Schedule</button>
			
			<select class="selectpicker selectView show-tick mr-2 float-right" data-width="180px" data-size="20" data-style="btn-dark btn-sm" title="-- Table Settings --" multiple>
				<?
				if(isset($_COOKIE["_showintable"])) $activeselectviews = json_decode($_COOKIE["_showintable"],true);
				
				echo '<option value="classrooms" '.(in_array('classrooms',$activeselectviews) ? 'selected' : '').'>Classrooms</option>';
				echo '<option value="departments" '.(in_array('departments',$activeselectviews) ? 'selected' : '').'>Departments</option>';
				echo '<option value="teachers" '.(in_array('teachers',$activeselectviews) ? 'selected' : '').'>Teachers</option>';
				?>
			</select>
		</div>
		<div class="tablecontainer"></div>
		
		<div class="dfooter text-center text-muted small pb-2 mt-2">
		Please open browser <b>cookies</b> to use this system properly.<br>
		The actual course program is taken from Atılım's web page.
		<?
		$last_updatedtime = $db->query("SELECT updatedtime FROM programs LIMIT 1")->fetch_object()->updatedtime;
		echo ' Schedule last updated on <b>'.date("d.m.Y ",$last_updatedtime).'</b> <a class="text-info" href="https://atilimengr.edupage.org/timetable/?&lang=tr" title="https://atilimengr.edupage.org/timetable/?&lang=tr" rel="nofollow noreferrer" target="_blank">(Source)</a>';
		?>
		<br> This system has been created to facilitate the selection of students who have been studying as <b>irregular</b> in Atılım.
		
		<br>Copyright &copy; 2019
		
		</div>
	</div>
</div>


<div class="dropdown-menu dropdown-menu-sm" id="contextMenu"></div>


</div>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,100,300,400italic,500italic,500,700,900&amp;subset=latin,latin-ext" type="text/css">
</div>
</div>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-132747595-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'UA-132747595-1');
</script>
</body>
</html>
<? @ob_end_flush();?>