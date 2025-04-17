<?php
$servername = "sql200.infinityfree.com";
$username = "if0_38736734";
$password = "JCWfPUWfNrF";
$dbname = "if0_38736734_university";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$access_code = $_POST['access_code'];

$today = date('l');              // e.g., Monday
$current_time = date('H:i:s');   // current time

$sql = "SELECT teacher_id, full_name FROM teachers WHERE access_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $access_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    $teacher_id = $teacher['teacher_id'];
    $full_name = $teacher['full_name'];

    // Find the scheduled course
    $course_sql = "SELECT c.course_id, c.course_name, c.start_time 
                   FROM teacher_courses tc 
                   JOIN courses c ON tc.course_id = c.course_id
                   WHERE tc.teacher_id = ? AND c.day_of_week = ?
                   ORDER BY c.start_time ASC LIMIT 1";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("is", $teacher_id, $today);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();

    if ($course_result->num_rows > 0) {
        $course = $course_result->fetch_assoc();
        $course_id = $course['course_id'];
        $course_name = $course['course_name'];
        $start_time = $course['start_time'];

        // Compare current time vs start_time
        $status = ($current_time > $start_time) ? "late" : "success";

        // Log it
        $log_sql = "INSERT INTO teacher_logs (teacher_id, course_id, status, log_date) 
                    VALUES (?, ?, ?, CURDATE())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("iis", $teacher_id, $course_id, $status);
        $log_stmt->execute();

        echo json_encode([
            "status" => $status, 
            "full_name" => $full_name,
            "course_name" => $course_name,
            "scheduled_time" => $start_time
        ]);
    } else {
        echo json_encode(["status" => "no_course_today"]);
    }
} else {
    echo json_encode(["status" => "failed"]);
}

$conn->close();
?>
