<?php @session_start();@ob_start();
ini_set('max_execution_time', 0);
set_time_limit(0);@header("Content-Type: text/html; charset=utf-8");@ob_end_clean();
error_reporting(1);
$starsArr = array();
$izin = "var";
include_once 'inc.php';
ob_implicit_flush(1);

$timetablepageMain = bot_curl("https://atilimengr.edupage.org/timetable/view.php");
preg_match_all("/ASC\.gsechash=\"(.*?)\";/is", $timetablepageMain, $contentmatched);
//var_dump($contentmatched[1][0]);
$secCode = $contentmatched[1][0];
//echo htmlentities($timetablepage2);
//$timetablepage = bot_postcurl("https://atilimengr.edupage.org/timetable/server/ttviewer.js?__func=getTTViewerData");
echo $secCode;
echo '<hr>';
$timetablepage = bot_postcurl("https://atilimengr.edupage.org/timetable/server/regulartt.js?__func=regularttGetData", '{"__args":[null,"17"],"__gsh":"' . $secCode . '"}');

//preg_match_all("/return f\(gi([0-9]+),{(.*?)}\);/is",$timetablepage,$contentmatched);
//$timetablepage = file_get_contents("test.txt");
//var_dump($contentmatched[2][0]);
//$test = json_decode('{'.$contentmatched[2][0].'}',true);
$orj = json_decode($timetablepage, true);
$test = json_decode($timetablepage, true);

if (count($test) < 1) {
    die("Ders programı çekilemedi..");
}

$test = $test["r"]["dbiAccessorRes"]["tables"];

//echo json_encode($test);
//die();
$mydbtables = ["cards", "classes", "classrooms", "lessons", "days", "periods", "programs", "subjects", "teachers", "usedperiods"];
foreach ($mydbtables AS $mytablename) {
    $db->query("TRUNCATE " . $mytablename);
    echo $mytablename . ' table truncated<hr>';
}

echo count($test[10]["data_rows"]) . "<hr>";
foreach ($test[0]["data_rows"] AS $key => $ss) {
    //var_dump($ss);
    echo $key . " > " . $ss["name"] . "<br>";
}

#PROGRAM NAME GÜZ BAHAR YIL
$db->query("INSERT INTO programs (name,short,updatedtime,verify) VALUES('" . mysqli_real_escape_string($db, $test[0]["data_rows"]["reg_name"]) . "','" . mysqli_real_escape_string($db, $test[0]["data_rows"]["year"]) . "'," . time() . ",'" . mysqli_real_escape_string($db, $orj["ce"]["ttdocs"]) . "')");

//echo var_dump($test);
//echo var_dump($test[13]["data_rows"] );
//die();

#SUBJECTS
foreach ($test[13]["data_rows"] AS $subjects) {
    //$idprefix = substr($subjects["id"], 0, 1);
    //$id = str_replace($idprefix,"",$subjects["id"]);
    $id = $subjects["id"];

    echo "<br>" . $idprefix . '|' . $id . " > " . $subjects["id"] . " >" . $subjects["name"] . " - " . $subjects["short"] . "<br>";
    preg_match_all("/([a-zA-Z]{1,5}[0-9]{1,4})/i", $subjects["short"], $matches);
    $codename = $matches[0][0];
    preg_match("/\(([\-0-9]+)\)/i", $subjects["short"], $sections);
    $section = $sections[1];
    $subjectname = explode("-", $subjects["name"]);
    $subjectname = end($subjectname);

    $db->query("INSERT INTO subjects (id,code,name,short,section,color) VALUES('$id','" . str_value($codename) . "','" . str_value($subjectname) . "','" . str_value($subjects["short"]) . "'," . ($section ? "'" . str_value($section) . "'" : '0') . ",'" . $subjects["color"] . "')");
    //echo "INSERT INTO subjects (id,code,name,short,section,color) VALUES($id,'".str_value($codename)."','".str_value($subjectname)."','".str_value($subjects["short"])."',".($section ? "'".str_value($section)."'" : '0').",'".$subjects["color"]."')";
}

#MAKE SAME COLOR EACH SUBJECT
$query_subject = $db->query("SELECT subjects.* FROM subjects GROUP BY code,section");
while ($row_sub = $query_subject->fetch_object()) {
    $db->query("UPDATE subjects SET color='" . $row_sub->color . "' WHERE code='" . $row_sub->code . "' AND section='" . $row_sub->section . "' ");
}

#LESSONS
foreach ($test[18]["data_rows"] AS $lesson) {
    //var_dump($lesson);
    //$idprefix = substr($lesson["id"], 0, 1);
    //$id = str_replace($idprefix,"",$lesson["id"]);
    $id = $lesson["id"];

    //$subjectidprefix = substr($lesson["subjectid"], 0, 1);
    //$subjectid = str_replace($subjectidprefix,"",$lesson["subjectid"]);
    $subjectid = $lesson["subjectid"];

    $duration = $lesson["durationperiods"];
    $teacherids = array_map(function ($v) {return (int) str_replace(substr($v, 0, 1), "", $v);}, $lesson["teacherids"]);
    $groupids = array_map(function ($v) {return (int) str_replace(substr($v, 0, 1), "", $v);}, $lesson["groupids"]);
    $classids = array_map(function ($v) {return (int) str_replace(substr($v, 0, 1), "", $v);}, $lesson["classids"]);
    $classroomids = array_map(function ($v) {return (int) str_replace(substr($v, 0, 1), "", $v);}, reset($lesson["classroomidss"]));

    $termsdefid = str_replace(substr($lesson["termsdefid"], 0, 1), "", $lesson["termsdefid"]);
    $weeksdefid = str_replace(substr($lesson["weeksdefid"], 0, 1), "", $lesson["weeksdefid"]);
    $daysdefid = str_replace(substr($lesson["daysdefid"], 0, 1), "", $lesson["daysdefid"]);
    $terms = $lesson["terms"];

    $db->query("INSERT INTO lessons (id,subjectid,teacherids,groupids,classids,duration,classroomids,termsdefid,weeksdefid,daysdefid,terms) VALUES('$id','$subjectid'," . ($teacherids ? "'" . json_encode($teacherids) . "'" : 'NULL') . ",'" . json_encode($groupids) . "','" . json_encode($classids) . "',$duration,'" . json_encode($classroomids) . "',$termsdefid,$weeksdefid,$daysdefid,$terms)");
    echo "INSERT INTO lessons (id,subjectid,teacherids,groupids,classids,duration,classroomids,termsdefid,weeksdefid,daysdefid,terms) VALUES($id,$subjectid," . ($teacherids ? "'" . json_encode($teacherids) . "'" : 'NULL') . ",'" . json_encode($groupids) . "','" . json_encode($classids) . "',$duration,'" . json_encode($classroomids) . "',$termsdefid,$weeksdefid,$daysdefid,$terms)<hr>";
    unset($lesson);
}

#PERIODS
foreach ($test[1]["data_rows"] AS $period) {
    var_dump($period);
    $db->query("INSERT INTO periods (id,period,name,short,starttime,endtime) VALUES(" . $period["period"] . "," . $period["period"] . ",'" . $period["name"] . "','" . $period["short"] . "','" . $period["starttime"] . "','" . $period["endtime"] . "')");
}

#DAYSDEF
foreach ($test[4]["data_rows"] AS $daysdef) {
    $id = str_replace(substr($daysdef["id"], 0, 1), "", $daysdef["id"]);
    //var_dump($daysdef);
    echo "INSERT INTO days (id,name,short,type,vals,val) VALUES(" . $id . ",'" . $daysdef["name"] . "','" . $daysdef["short"] . "','" . $daysdef["typ"] . "'," . ($daysdef["vals"] ? "'" . json_encode($daysdef["vals"]) . "'" : 'NULL') . ",'" . $daysdef["val"] . "')<br>";
    $db->query("INSERT INTO days (id,name,short,type,vals,val) VALUES(" . $id . ",'" . $daysdef["name"] . "','" . $daysdef["short"] . "','" . $daysdef["typ"] . "'," . ($daysdef["vals"] ? "'" . (reset($daysdef["vals"])) . "'" : 'NULL') . "," . ($daysdef["val"] ? $daysdef["val"] : 'NULL') . ")");
}

#CLASSES
foreach ($test[12]["data_rows"] AS $class) {
    $id = str_replace(substr($class["id"], 0, 1), "", $class["id"]);
    //var_dump($class);
    $explodedname = explode(" ", $class["name"]);
    $explodedname = reset($explodedname);
    if ($explodedname == "Genel") {
        $explodedname = "GeneralElectives";
    }

    echo "INSERT INTO classes (id,name,short,color,department) VALUES(" . $id . ",'" . $class["name"] . "','" . $class["short"] . "','" . $class["color"] . "','" . ($explodedname) . "')<br>";
    $db->query("INSERT INTO classes (id,name,short,color,department) VALUES(" . $id . ",'" . $class["name"] . "','" . $class["short"] . "','" . $class["color"] . "','" . ($explodedname) . "')");
}

#CLASSROOMS
foreach ($test[11]["data_rows"] AS $classroom) {
    $id = str_replace(substr($classroom["id"], 0, 1), "", $classroom["id"]);
    var_dump($classroom);
    $db->query("INSERT INTO classrooms (id,name,short,color) VALUES(" . $id . ",'" . mysqli_real_escape_string($db, $classroom["name"]) . "','" . mysqli_real_escape_string($db, $classroom["short"]) . "','" . $classroom["color"] . "')");
}

#CARDS
foreach ($test[20]["data_rows"] AS $card) {
    //$id = str_replace(substr($card["id"], 0, 1),"",$card["id"]);
    $id = $card["id"];
    //$lessonid = str_replace(substr($card["lessonid"], 0, 1),"",$card["lessonid"]);
    $lessonid = $card["lessonid"];
    $classroomids = array_map(function ($v) {return (int) str_replace(substr($v, 0, 1), "", $v);}, ($card["classroomids"]));
    var_dump($card);
    echo "INSERT INTO cards (id,lessonid,period,days,weeks,classroomids) VALUES('" . $id . "','" . $lessonid . "','" . $card["period"] . "','" . $card["days"] . "','" . $card["weeks"] . "'," . (is_array($card["classroomids"]) ? "'" . json_encode($classroomids) . "'" : 'NULL') . ")<hr>";
    $db->query("INSERT INTO cards (id,lessonid,period,days,weeks,classroomids) VALUES('" . $id . "','" . $lessonid . "','" . $card["period"] . "','" . $card["days"] . "','" . $card["weeks"] . "'," . (is_array($card["classroomids"]) ? "'" . json_encode($classroomids) . "'" : 'NULL') . ")");
}

#TEACHERS
foreach ($test[14]["data_rows"] AS $teacher) {
    $id = str_replace(substr($teacher["id"], 0, 1), "", $teacher["id"]);
    var_dump($teacher);

    $db->query("INSERT INTO teachers (id,short,firstname,lastname,color,printheaderprefix) VALUES(" . $id . ",'" . $teacher["short"] . "','" . $teacher["firstname"] . "','" . $teacher["lastname"] . "','" . $teacher["color"] . "','" . $teacher["printheaderprefix"] . "')");
}

#NEW TABLE USEDPERIODS
$query = $db->query("
					SELECT subjects.*,cards.period,periods.starttime,periods.endtime,cards.days,cards.weeks,cards.classroomids,lessons.duration,days.name AS dayname,days.id AS dayid, subjects.color
					,lessons.teacherids,lessons.classids,lessons.id AS lessonsid, subjects.id AS subjectid, cards.id AS cardid
					FROM cards
					INNER JOIN lessons ON cards.lessonid = lessons.id
					INNER JOIN subjects ON lessons.subjectid = subjects.id
					INNER JOIN periods ON cards.period = periods.period
					INNER JOIN days ON days.vals = cards.days

					");

while ($row_query = $query->fetch_object()) {
    $durationadded = 0;
    while ($durationadded < $row_query->duration) {
        $db->query("INSERT INTO usedperiods(code,section,lessonid,classids,period,dayid,dp) VALUES('" . str_value($row_query->code) . "','" . str_value($row_query->section) . "','" . str_value($row_query->lessonsid) . "','" . str_value($row_query->classids) . "','" . int_value($row_query->period + $durationadded) . "','" . $row_query->dayid . "'," . $row_query->dayid . ($row_query->period + $durationadded) . ")");
        $durationadded++;
    }
    echo "PERIOD INSERTED. <HR>";
}

echo '<hr>Zaman: ' . time() . ' = ' . date("H:i:s -- d.m.Y", time());
//var_dump($test["changes"][1]["rows"]);
//echo '<pre>'.$getir[0].'</pre>';

?>