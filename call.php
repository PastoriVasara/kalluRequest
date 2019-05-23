<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
include('../connectSQL.php');

if (isset($_POST['unit']) || isset($_POST['course'])) {
    $unit = isset($_POST['unit']) ? $_POST['unit'] : null;
    $condition = isset($_POST['condition']) ? $_POST['condition'] : null;
    
    $baseQuery = 'SELECT  linkedcourse.ID AS Parent, parent.CODE as Parent_Code, parent.NAME AS Parent_Course, linkedcourse.LINKEDID AS Child, child.CODE AS Child_Code, child.$
    FROM linkedcourse JOIN course_shrinked parent ON linkedcourse.ID = parent.ID JOIN course_shrinked child ON linkedcourse.LINKEDID = child.ID WHERE ';
    $queryModifiers = '';
    for($i = 0; $i < count($unit); $i++)
    {
        if($i != 0 && $i != count($unit)-1)
        {
            $queryModifiers .= $condition[$i];
        }
        $queryModifiers .= '(parent.UNIT = "'.$unit[$i].'" OR child.UNIT = "'.$unit[$i].'")'; 
    }
    $queryModifiers .= 'ORDER BY Parent ASC';
    $fullQuery =$baseQuery.$queryModifiers;

    $IDsFROM = array();
    $childrenIDsFROM = array();
    $courseNamesFROM = array();

    $IDsTO = array();
    $childrenIDsTO = array();
    $courseNamesTO = array();
    $test = mysqli_query($conn,$fullQuery);
    if ($returnValue = mysqli_query($conn, $fullQuery)) {
        if (mysqli_num_rows($returnValue) > 0) {
            while ($row = mysqli_fetch_array($returnValue)) {
                $IDsFROM[] = $row[0];
                $childrenIDsFROM[] = preg_replace('/[^(\x20-\x7F)]*/', '', $row[1]);
                $courseNamesFROM[] =  utf8_encode($row[2]);
                $IDsTO[] = $row[3];
                $childrenIDsTO[] =preg_replace('/[^(\x20-\x7F)]*/', '', $row[4]);
                $courseNamesTO[] =  utf8_encode($row[5]);
            }
       }
   }
 echo json_encode(array($IDsFROM,$childrenIDsFROM, $courseNamesFROM, $IDsTO , $childrenIDsTO , $courseNamesTO));

} elseif (isset($_POST['courses'])) {
    $fullQuery = 'SELECT NAME, CODE FROM course';
    $courseName = array();
    $courseCode = array();
    if ($returnValue = mysqli_query($conn, $fullQuery)) {
        if (mysqli_num_rows($returnValue) > 0) {
            while ($row = mysqli_fetch_array($returnValue)) {
                $courseName[] =  utf8_encode($row[0]);
                $courseCode[] = preg_replace('/[^(\x20-\x7F)]*/', '', $row[1]);
            }
        }
    }
    echo json_encode(array($courseName,$courseCode));
}
?>

