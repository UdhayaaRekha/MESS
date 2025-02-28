<?php
// calculate_bill.php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['total_grocery'])) {
    $total_grocery = $_POST['total_grocery'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Define the date range for the selected month
    $start_date = "$year-$month-01";
    $total_days_in_month = date("t", strtotime($start_date)); // Get the total number of days in the month
    $end_date = "$year-$month-$total_days_in_month"; // Last day of the month

    // Fetch attendance for each student
    $sql = "SELECT attendance.reg_no, 
                   users.name, 
                   COUNT(CASE WHEN attendance.status = 'Absent' THEN 1 END) AS total_absent_days
            FROM attendance_system.attendance AS attendance
            JOIN user_management.users AS users ON attendance.reg_no = users.reg_no
            WHERE attendance.date BETWEEN '$start_date' AND '$end_date'
            GROUP BY attendance.reg_no";

    $result = $conn->query($sql);
    $total_present_days = 0;
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $reg_no = $row['reg_no'];
        $student['reg_no'] = $row['reg_no'];
        $student['name'] = $row['name'];
        $student['total_absent_days'] = $row['total_absent_days'];
        $student['present_days'] = $total_days_in_month - $student['total_absent_days'];

        // Check if absent for the whole month (bill should be zero)
        if ($student['total_absent_days'] == $total_days_in_month) {
            $student['final_present_days'] = 0;
            $student['reduction_days'] = 0;
        } else {
            // N-2 reduction if absences ≥ 7
            $valid_absent_days = $student['total_absent_days'];

            // Check consecutive absences at start and end of the month
            $consecutive_start = 0;
            $consecutive_end = 0;

            for ($i = 1; $i <= 5; $i++) {
                $date = "$year-$month-0$i";
                $check_sql = "SELECT status FROM attendance_system.attendance 
                              WHERE reg_no = '$reg_no' AND date = '$date' AND status = 'Absent'";
                $check_result = $conn->query($check_sql);
                if ($check_result->num_rows > 0) {
                    $consecutive_start++;
                } else {
                    break;
                }
            }

            for ($i = $total_days_in_month; $i > $total_days_in_month - 5; $i--) {
                $date = "$year-$month-$i";
                $check_sql = "SELECT status FROM attendance_system.attendance 
                              WHERE reg_no = '$reg_no' AND date = '$date' AND status = 'Absent'";
                $check_result = $conn->query($check_sql);
                if ($check_result->num_rows > 0) {
                    $consecutive_end++;
                } else {
                    break;
                }
            }

            // Ignore these periods if each is less than 7 days separately
            if ($consecutive_start < 7) $consecutive_start = 0;
            if ($consecutive_end < 7) $consecutive_end = 0;

            $valid_absent_days -= ($consecutive_start + $consecutive_end);

            if ($valid_absent_days >= 7) {
                $student['reduction_days'] = max(0, $valid_absent_days - 2);
            } else {
                $student['reduction_days'] = 0; // No reduction if less than 7 absences
            }

            $student['final_present_days'] = $total_days_in_month - $student['reduction_days'];
        }

        // Add to total present days
        $total_present_days += $student['final_present_days'];

        $students[] = $student;
    }

    // *Fix: Correct Per-Day Bill Calculation*
    $per_day_bill = $total_present_days > 0 ? $total_grocery / $total_present_days : 0;

    // Calculate bill for each student
    foreach ($students as &$student) {
        $student['bill_amount'] = $student['final_present_days'] * $per_day_bill;
    }

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Mess Bill Calculation</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f9f9f9;
                margin: 0;
                padding: 20px;
                text-align: center;
            }
            h2 {
                color: #a52a2a;
            }
            table {
                width: 80%;
                margin: 20px auto;
                border-collapse: collapse;
            }
            th, td {
                padding: 10px;
                text-align: center;
                border: 1px solid #ddd;
            }
            th {
                background-color: #a52a2a;
                color: white;
            }
            .btn-container {
                margin: 20px;
            }
            .btn {
                background-color: #a52a2a;
                color: white;
                padding: 10px 20px;
                border: none;
                cursor: pointer;
                font-size: 16px;
            }
            .btn:hover {
                background-color: #800000;
            }
        </style>
        <script>
            function printPage() {
                window.print();
            }
        </script>
    </head>
    <body>";

    echo "<h2>Mess Bill Calculation for " . date("F", mktime(0, 0, 0, $month, 1)) . " $year</h2>";
    echo "<h3>Total Grocery Amount: ₹ " . number_format($total_grocery, 2) . "</h3>";
   
    echo "<h2>Per Day Mess Bill: ₹" . number_format($per_day_bill, 2) . "</h2>";
    echo "<h3>Bill Division:</h3>";
    echo "<table>
            <tr>
                <th>Register Number</th>
                <th>Name</th>
                <th>Total Absent Days</th>
                <th>Present Days</th>
                <th>Reduction Days</th>
                <th>Final Present Days</th>
                <th>Bill Amount</th>
            </tr>";

    foreach ($students as $student) {
        echo "<tr>
                <td>" . htmlspecialchars($student['reg_no']) . "</td>
                <td>" . htmlspecialchars($student['name']) . "</td>
                <td>" . htmlspecialchars($student['total_absent_days']) . "</td>
                <td>" . htmlspecialchars($student['present_days']) . "</td>
                <td>" . htmlspecialchars($student['reduction_days']) . "</td>
                <td>" . htmlspecialchars($student['final_present_days']) . "</td>
                <td>₹ " . number_format($student['bill_amount'], 2) . "</td>
              </tr>";
    }

    echo "</table>";

    echo "<div class='btn-container'>
            <button class='btn' onclick='printPage()'>Generate PDF</button>
          </div>";

    echo "</body></html>";

    $conn->close();
} else {
    header("Location: enter_grocery.php");
    exit();
}
?>