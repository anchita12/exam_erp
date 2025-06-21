<?php 
include("scripts/settings.php");

$msg = '';
page_header_start();
page_header_end();
page_sidebar();

// Pagination Settings
$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
?>
<style>
.pagination {
    display: inline-block;
    padding: 10px 0;
}
.pagination a, .pagination strong {
    color: #007bff;
    float: left;
    padding: 8px 16px;
    text-decoration: none;
    margin: 0 4px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-weight: bold;
    transition: all 0.3s ease-in-out;
}
.pagination a:hover {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}
.pagination strong {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

table.table-striped tr:nth-child(odd) {
    background: #eeeeee;
}
table.table-striped th, table.table-striped td {
    padding: 8px;
    text-align: center;
}
</style>

<div id="container">
    <div class="card card-body">
        <div class="bg-secondary text-white text-center p-2"><h3>Student Paper Report</h3></div>

        <!-- Class and Semester Filter Dropdown -->
        <form method="GET" action="">
            <div class="form-group mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <label for="semester_filter"><strong>Select Semester:</strong></label>
                        <select name="semester" id="semester_filter" class="form-control" onchange="this.form.submit()">
                            <option value="">-- All Semesters --</option> -->
                             <option value="1" <?php echo isset($_GET['semester']) && $_GET['semester'] == '1' ? 'selected' : ''; ?>>1st Semester</option> -->
                             <option value="2" <?php echo isset($_GET['semester']) && $_GET['semester'] == '2' ? 'selected' : ''; ?>>2nd Semester</option> --> -->
                            <option value="3" <?php echo isset($_GET['semester']) && $_GET['semester'] == '3' ? 'selected' : ''; ?>>3rd Semester</option> -->
                            <option value="4" <?php echo isset($_GET['semester']) && $_GET['semester'] == '4' ? 'selected' : ''; ?>>4th Semester</option> -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="class_filter"><strong>Select Class:</strong></label>
                        <select name="class_id" id="class_filter" class="form-control" onchange="this.form.submit()">
                            <option value="">-- All Classes --</option>
                            <?php
                             $sql_classes = 'SELECT * FROM class_detail WHERE semester IN ("1","2","3","4")';
                            if (isset($_GET['semester']) && $_GET['semester'] != '') {
                                $sql_classes .= ' AND semester = "' . mysqli_real_escape_string($db, $_GET['semester']) . '"';
                            }
                            $sql_classes .= ' ORDER BY ABS(group_short) ASC, ABS(semester) ASC';
                            $result_classes = execute_query($db, $sql_classes);
                            while ($class_row = mysqli_fetch_assoc($result_classes)) {
                                $selected = isset($_GET['class_id']) && $_GET['class_id'] == $class_row['sno'] ? 'selected' : '';
                                echo '<option value="' . $class_row['sno'] . '" ' . $selected . '>' . $class_row['class_description'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <!-- Student Paper Report Table -->
        <table width="100%" class="table table-striped table-hover rounded">
            <tr class="bg-secondary text-white text-center p-2" align="center">
                <th>Sno.</th>
                <th>UIN NO</th>
                <th>Student Name</th>
                 <th>Father Name</th>
                <th>Exam Roll No</th>
                <th>College Roll No</th>
                <th>DOB</th>
                <th>Mobile No</th>
                <th>Class</th>
                <th>Paper Details</th>
            </tr>

            <?php
            $class_filter = isset($_GET['class_id']) ? (int)$_GET['class_id'] : '';
            $semester_filter = isset($_GET['semester']) ? mysqli_real_escape_string($db, $_GET['semester']) : '';
            $i = $offset + 1;

            if (!empty($class_filter) || !empty($semester_filter)) {
               $sql = 'SELECT 
            esi.sno as student_id,
            esi.uin_no,
            esi.student_name, 
            si.father_name,
            esi.exam_roll_no, 
            esi.college_roll_no, 
            esi.dob, 
            esi.mobile_no, 
           
            cd.class_description, 
            GROUP_CONCAT(CONCAT(espi.title_of_paper, " (", espi.paper_code, ")") SEPARATOR ", ") AS paper_details
        FROM exam_student_info esi
        INNER JOIN student_info si ON si.sno = esi.student_info_sno
        INNER JOIN exam_student_paper_info espi ON esi.sno = espi.exam_student_info_sno
        INNER JOIN class_detail cd ON espi.class_id = cd.sno';

                $conditions = [];

                if (!empty($class_filter)) {
                    $conditions[] = 'cd.sno = ' . $class_filter;
                }
                if (!empty($semester_filter)) {
                    $conditions[] = 'cd.semester = "' . $semester_filter . '"';
                }

                if (!empty($conditions)) {
                    $sql .= ' WHERE ' . implode(' AND ', $conditions);
                }

                $sql .= ' GROUP BY esi.sno, cd.class_description';

                // Count total rows for pagination
                $result_total = execute_query($db, $sql);
                $total_records = mysqli_num_rows($result_total);

                // Add limit for pagination
                $sql .= " LIMIT $offset, $limit";

                $result = execute_query($db, $sql);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr align="center">'
    . '<td>' . $i++ . '</td>'
    . '<td>' . $row['uin_no'] . '</td>'
    . '<td>' . $row['student_name'] . '</td>'
     . '<td>' . $row['father_name'] . '</td>'
    . '<td>' . $row['exam_roll_no'] . '</td>'
     . '<td>' . $row['college_roll_no'] . '</td>'
    . '<td>' . $row['dob'] . '</td>'
    . '<td>' . $row['mobile_no'] . '</td>'
    . '<td>' . ($row['class_description'] ?? 'N/A') . '</td>'
    . '<td>' . $row['paper_details'] . '</td>'
    . '</tr>';

                    }
                } else {
                    echo '<tr><td colspan="10" align="center">No records found for selected class or semester.</td></tr>';
                }

                // Pagination links
       $total_pages = ceil($total_records / $limit);
       $pages_per_group = 10;
       $current_group = ceil($page / $pages_per_group);
       $start_page = ($current_group - 1) * $pages_per_group + 1;
       $end_page = min($start_page + $pages_per_group - 1, $total_pages);

if ($total_pages > 1) {
    echo '<tr><td colspan="10" align="center"><div class="pagination">';

    // Prev Group
    if ($start_page > 1) {
        $prev_group_page = $start_page - 1;
        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $prev_group_page])) . '">&laquo; Prev</a>';
    }

    // Page numbers
    for ($p = $start_page; $p <= $end_page; $p++) {
        if ($p == $page) {
            echo '<strong>' . $p . '</strong>';
        } else {
            echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $p])) . '">' . $p . '</a>';
        }
    }

    // Next Group
    if ($end_page < $total_pages) {
        $next_group_page = $end_page + 1;
        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $next_group_page])) . '">Next &raquo;</a>';
    }

    echo '</div></td></tr>';
}


            } else {
                echo '<tr><td colspan="10" align="center">Please select at least a class or semester to view records.</td></tr>';
            }
            ?>
        </table>
    </div>
</div>

<?php
page_footer_start();
page_footer_end();
?>
