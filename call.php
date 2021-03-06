<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
include('../connectSQL.php');

if (isset($_POST['unit']) || isset($_POST['course'])) {
    $unit = isset($_POST['unit']) ? $_POST['unit'] : array("MORO");
    $courses = isset($_POST['course']) ? $_POST['course'] : array("MORO");
    $fullQuery = '';
    $courseModifiers = '';
    if (count($courses) > 0) {
        for ($i = 0; $i < count($courses); $i++) {
            $courseModifiers .= ' (linkedcourse.ID = "'.$courses[$i].'" OR linkedcourse.LINKEDID = "'.$courses[$i].'") ';
            if($i != count($courses) -1 && count($courses) != 1)
            {
                $courseModifiers .= 'OR';
            }
        }
    }
    if($courseModifiers != '')
    {
        $fullQuery .= ' WITH RECURSIVE courseRecursion
        AS
        (
        SELECT linkedcourse.*, 0 AS specified
        FROM linkedcourse
        WHERE '.$courseModifiers.'
        UNION
        SELECT f.*, 1 AS specified
        FROM linkedcourse AS f, courseRecursion AS a
        WHERE f.ID = a.ID OR f.ID = a.LINKEDID
        )
        SELECT
            courseRecursion.ID AS Parent,
            parent.CODE AS Parent_Code,
            parent.NAME AS Parent_Course,
            courseRecursion.LINKEDID AS Child,
            child.CODE AS Child_Code,
            child.NAME AS Child_Course,
            courseRecursion.specified +1 AS specified
        FROM
            courseRecursion
        JOIN course parent ON
            courseRecursion.ID = parent.ID
        JOIN course child ON
            courseRecursion.LINKEDID = child.ID
        ORDER BY courseRecursion.specified;';
    }
    $baseQuery = 'SELECT Parent,Parent_Code,Parent_Course,Child,Child_Code,Child_Course,0 AS specified FROM linkedcoursewithinfo WHERE ';
    $baseQuery = preg_replace( "/\r|\n/", "", $baseQuery );
    $queryModifiers = '';
    for($i = 0; $i < count($unit); $i++)
    {

        $queryModifiers .= ' (Parent_Unit = "'.$unit[$i].'" OR Child_Unit = "'.$unit[$i].'") ';
        if($i != count($unit) -1 && count($unit) != 1)
        {
            //$queryModifiers .= $condition[$i];
            $queryModifiers .= 'OR';
        }
    }
    $queryModifiers .= 'ORDER BY Parent_Code ASC;';
    $fullQuery .=$baseQuery.$queryModifiers;
   
    $fasterArrayID = array();
    $fasterArrayShortName = array();
    $fasterArrayLongName = array();

    $IDsFROM = array();
    $childrenIDsFROM = array();
    $courseNamesFROM = array();

    $IDsTO = array();
    $childrenIDsTO = array();
    $courseNamesTO = array();
    $divider = array();

    if (mysqli_multi_query($conn, $fullQuery)) {
        do {
            /* store first result set */
            if ($result = mysqli_store_result($conn)) {
                while ($row = mysqli_fetch_row($result)) {

                        $IDsFROM[] = $row[0];
                        $childrenIDsFROM[] = preg_replace('/[^(\x20-\x7F)]*/', '', $row[1]);
                        $courseNamesFROM[] =  utf8_encode($row[2]);
                        $IDsTO[] = $row[3];
                        $childrenIDsTO[] =preg_replace('/[^(\x20-\x7F)]*/', '', $row[4]);
                        $courseNamesTO[] =  utf8_encode($row[5]);
                        $divider[] = utf8_encode($row[6]);                 
                }
                mysqli_free_result($result);
            }
            /* print divider */
            if (mysqli_more_results($conn)) {
            }
        } while (mysqli_next_result($conn));
    }
 echo json_encode(array($IDsFROM,$childrenIDsFROM, $courseNamesFROM, $IDsTO , $childrenIDsTO , $courseNamesTO, $divider,$fasterArrayID,$fasterArrayShortName,$fasterArrayLongName));
 // Error Query
 //$IDsFROM $fullQuery
 //echo json_encode(array($fullQuery,$childrenIDsFROM, $courseNamesFROM, $IDsTO , $childrenIDsTO , $courseNamesTO));

} elseif (isset($_POST['courses']) && $_POST['courses'] == 'all') {
    //$fullQuery = 'SELECT NAME, CODE, ID FROM course_shrinked';
    //$fullQuery = 'SELECT NAME, CODE, ID FROM course WHERE course.ID IN(SELECT ID ids FROM linkedcourse UNION ALL SELECT LINKEDID linkedids FROM linkedcourse)';
    $fullQuery = 'SELECT * FROM courseselector';
    $courseName = array();
    $courseCode = array();
    $courseID = array();
    if ($returnValue = mysqli_query($conn, $fullQuery)) {
        if (mysqli_num_rows($returnValue) > 0) {
            while ($row = mysqli_fetch_array($returnValue)) {
                $courseName[] =  utf8_encode($row[0]);
                $courseCode[] = preg_replace('/[^(\x20-\x7F)]*/', '', $row[1]);
                $courseID[] = $row[2];
            }
        }
    }
    echo json_encode(array($courseName,$courseCode,$courseID));
} elseif(isset($_POST['tablePeriods']) || isset($_POST['tableDegrees']) || isset($_POST['tableUnits']))
{
    $units = isset($_POST['tableUnits']) ? $_POST['tableUnits'] : null;
    $degrees = isset($_POST['tableDegrees']) ? $_POST['tableDegrees'] : null;
    $periods = isset($_POST['tablePeriods']) ? $_POST['tablePeriods'] : null;
    $fullQuery = 'SELECT course.*, @rownum := @rownum + 1 AS num FROM course, (SELECT @rownum := -1) r';
    $queryModifier = '';
    $periodModifier = '';
    if($periods != null)
    {
        $periodModifier .= ' AND (';
        for($i = 0; $i < count($periods); $i++)
        {
            if($i != 0)
            {
                $periodModifier .= 'OR '.$periods[$i].' =1 ';
            }
            else 
            {
                $periodModifier .= ' '.$periods[$i].' =1 ';
            }
        }
        $periodModifier .= ')';
    }
    if($units != null)
    {
        $queryModifier .= '((UNIT = "';
        for($i = 0; $i < count($units); $i++)
        {
            if($i != 0)
            {
                $queryModifier .= ' OR UNIT = "'.$units[$i].' "';
            }
            else 
            {
                $queryModifier .= $units[$i].'" ';
            }
        }
        $queryModifier .=  ')'.$periodModifier.')';
    }
    if($degrees != null)
    {
        if($queryModifier == '')
        {
            $queryModifier .= '((DEGREE = "';
        }
        else
        {
            $queryModifier .= ' OR ((DEGREE = "';  
        }
        
        for($i = 0; $i < count($degrees); $i++)
        {
            if($i != 0)
            {
                $queryModifier .= ' OR UNIT = "'.$degrees[$i].' "';
            }
            else 
            {
                $queryModifier .= $degrees[$i].'" ';
            }
        }
        $queryModifier .=  ')'.$periodModifier.')';
    }
    if($queryModifier != '')
    {
        $fullQuery .= ' WHERE '.$queryModifier;
    }
    //have to convert data to utf8mb4
    $conn->set_charset('utf8mb4');
    $data = $conn->query($fullQuery)->fetch_all(MYSQLI_ASSOC);
    echo json_encode($data);
}
elseif(isset($_POST['courseSchedule']))
{
    $courses = $_POST['courseSchedule'];
    $fullQuery = "SELECT * FROM lecture as u JOIN course AS b ON b.ID = u.ID  WHERE u.ID in( ";
        $text = "";
        $count = count($courses);
        for ($i = 0; $i < $count; $i++) {
            $text = $text.$courses[$i].",";
        }
        $text = rtrim($text, ",");
        $fullQuery = $fullQuery.$text.") ORDER BY u.ID, u.LECTURETYPE";

        $conn->set_charset('utf8mb4');
        $data = $conn->query($fullQuery)->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
}
?>