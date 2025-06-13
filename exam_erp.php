 <?php
include("scripts/settings.php");
$conn = dbconnect();

page_header_start();
page_header_end();
page_sidebar();

$info = null;
$paper = null;
$response = 1;
$fields = [
    "student_name" => "text",
    "college_roll_no" => "text",
    "exam_form_no" => "text",
    "exam_roll_no" => "text",
    "dob" => "date",
    "mobile_no" => "text",
    "uin_no" => "text",
    "course_name" => "text",
    "email" => "email",
    "aadhar" => "text",
    "category" => "text",
    "religion" => "text",
    "whatsapp_no" => "text",
    "p_mobile" => "text",
    "mother_tongue" => "text",
    "blood_group" => "text",
    "transaction_id" => "text",
    "max_marks" => "number",
    "obt_marks" => "number",
    "sgpa" => "number",
    "cgpa" => "number",
    "grade_point" => "number"
];

$paper_fields = [
    "type" => "text",
    "title_of_paper" => "text",
    "theory_practical" => "select",
    "pt_marks_max" => "number",
    "pt_marks_obt" => "number",
    "mid_sem_marks_max" => "number",
    "mid_sem_marks_obt" => "number"
];

// Handle "Back to Search" action
if (isset($_POST['back_to_search'])) {
    $info = null;
    $paper = null;
    $response = 1;
}

if (isset($_POST['search'])) {
    $exam_roll_no = trim($_POST['exam_roll_no'] ?? '');

    if (empty($exam_roll_no)) {
        echo "<div class='alert alert-danger'>Please enter a valid exam roll number.</div>";
    } else {
        $info_sql = "SELECT esi.*, cd.class_description AS course_name_display 
                     FROM exam_student_info esi 
                     LEFT JOIN class_detail cd ON esi.course_name = cd.sno 
                     WHERE esi.exam_roll_no = ?";
        $stmt = $conn->prepare($info_sql);
        $stmt->bind_param("s", $exam_roll_no);
        $stmt->execute();
        $info_result = $stmt->get_result();

        if ($info_result && $info_result->num_rows > 0) {
            $info = $info_result->fetch_assoc();
            $exam_id = $info['exam_id'];

            $paper_sql = "SELECT * FROM exam_student_paper_info WHERE exam_student_info_sno = ?";
            $stmt = $conn->prepare($paper_sql);
            $stmt->bind_param("i", $exam_id);
            $stmt->execute();
            $paper_result = $stmt->get_result();
            $paper = $paper_result->fetch_all(MYSQLI_ASSOC);
        } else {
            echo "<div class='alert alert-danger'>Student not found.</div>";
        }
    }
}

if (isset($_POST['update_all'])) {
    // Check if form is already processed
    if (isset($_POST['form_processed']) && $_POST['form_processed'] === '1') {
        $exam_id = $_POST['exam_id'];

        // Validate exam_id
        if (!is_numeric($exam_id) || $exam_id <= 0) {
            echo "<div class='alert alert-danger'>Invalid Exam ID.</div>";
            exit;
        }

        // Validate numeric fields for student info
        $numeric_fields = ['max_marks', 'obt_marks', 'sgpa', 'cgpa', 'grade_point'];
        foreach ($numeric_fields as $field) {
            if (!isset($_POST[$field]) || !is_numeric($_POST[$field])) {
                echo "<div class='alert alert-danger'>$field must be a valid number.</div>";
                exit;
            }
        }

        // Update student info
        $update_info = "UPDATE exam_student_info SET 
            student_name = ?, college_roll_no = ?, exam_form_no = ?, exam_roll_no = ?, 
            dob = ?, mobile_no = ?, uin_no = ?, course_name = ?, email = ?, aadhar = ?, 
            category = ?, religion = ?, whatsapp_no = ?, p_mobile = ?, mother_tongue = ?, 
            blood_group = ?, transaction_id = ?, max_marks = ?, obt_marks = ?, sgpa = ?, 
            cgpa = ?, grade_point = ? WHERE exam_id = ?";
        $stmt = $conn->prepare($update_info);
        $stmt->bind_param(
            "sssssssssssssssssdddddi",
            $_POST['student_name'], $_POST['college_roll_no'], $_POST['exam_form_no'], 
            $_POST['exam_roll_no'], $_POST['dob'], $_POST['mobile_no'], $_POST['uin_no'], 
            $_POST['course_name'], $_POST['email'], $_POST['aadhar'], $_POST['category'], 
            $_POST['religion'], $_POST['whatsapp_no'], $_POST['p_mobile'], 
            $_POST['mother_tongue'], $_POST['blood_group'], $_POST['transaction_id'], 
            $_POST['max_marks'], $_POST['obt_marks'], $_POST['sgpa'], 
            $_POST['cgpa'], $_POST['grade_point'], $exam_id
        );
        $student_updated = $stmt->execute();

        if ($student_updated) {
            // Update paper info
            if (!empty($_POST['type']) && !empty($_POST['subject_id'])) {
                $update_paper = "UPDATE exam_student_paper_info SET 
                    type = ?, title_of_paper = ?, theory_practical = ?, 
                    pt_marks_max = ?, pt_marks_obt = ?, mid_sem_marks_max = ?, mid_sem_marks_obt = ? 
                    WHERE exam_student_info_sno = ? AND subject_id = ?";
                
                // Fetch existing subject IDs
                $existing_papers_sql = "SELECT subject_id FROM exam_student_paper_info WHERE exam_student_info_sno = ?";
                $stmt = $conn->prepare($existing_papers_sql);
                $stmt->bind_param("i", $exam_id);
                $stmt->execute();
                $existing_papers_result = $stmt->get_result();
                $existing_paper_ids = [];
                while ($row = $existing_papers_result->fetch_assoc()) {
                    $existing_paper_ids[] = $row['subject_id'];
                }

                foreach ($_POST['type'] as $index => $type) {
                    if (
                        !empty($type) &&
                        !empty($_POST['title_of_paper'][$index]) &&
                        !empty($_POST['theory_practical'][$index]) &&
                        isset($_POST['pt_marks_max'][$index]) && is_numeric($_POST['pt_marks_max'][$index]) &&
                        isset($_POST['pt_marks_obt'][$index]) && is_numeric($_POST['pt_marks_obt'][$index]) &&
                        isset($_POST['mid_sem_marks_max'][$index]) && is_numeric($_POST['mid_sem_marks_max'][$index]) &&
                        isset($_POST['mid_sem_marks_obt'][$index]) && is_numeric($_POST['mid_sem_marks_obt'][$index]) &&
                        isset($_POST['subject_id'][$index]) && in_array($_POST['subject_id'][$index], $existing_paper_ids)
                    ) {
                        $stmt = $conn->prepare($update_paper);
                        $stmt->bind_param(
                            "ssssssssi",
                            $type,
                            $_POST['title_of_paper'][$index],
                            $_POST['theory_practical'][$index],
                            $_POST['pt_marks_max'][$index],
                            $_POST['pt_marks_obt'][$index],
                            $_POST['mid_sem_marks_max'][$index],
                            $_POST['mid_sem_marks_obt'][$index],
                            $exam_id,
                            $_POST['subject_id'][$index]
                        );
                        $stmt->execute();
                    }
                }
            }

            echo "<div class='alert alert-success'>Student and paper data updated successfully!</div>";
            $response = 2;

            // Fetch updated data
            $info_sql = "SELECT esi.*, cd.class_description AS course_name_display 
                         FROM exam_student_info esi 
                         LEFT JOIN class_detail cd ON esi.course_name = cd.sno 
                         WHERE esi.exam_id = ?";
            $stmt = $conn->prepare($info_sql);
            $stmt->bind_param("i", $exam_id);
            $stmt->execute();
            $info_result = $stmt->get_result();
            $info = $info_result->fetch_assoc();

            $paper_sql = "SELECT * FROM exam_student_paper_info WHERE exam_student_info_sno = ?";
            $stmt = $conn->prepare($paper_sql);
            $stmt->bind_param("i", $exam_id);
            $stmt->execute();
            $paper_result = $stmt->get_result();
            $paper = $paper_result->fetch_all(MYSQLI_ASSOC);
        } else {
            echo "<div class='alert alert-danger'>Error updating data: " . $conn->error . "</div>";
        }
    }
}
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
form div.row:nth-child(odd) {
    background: #eeeeee;
    border-radius: 5px;
    margin-bottom: 5px;
    margin-top: 5px;
    padding: 5px;
}

form div.row label {
    color: #000000;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    padding: 20px;
}

h2 {
    color: #000;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    text-align: center;
}

.card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.form-control {
    border-radius: 6px;
    font-size: 14px;
}

.btn-primary {
    background-color: #007bff;
    border: none;
    border-radius: 6px;
    padding: 10px 20px;
    font-size: 16px;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.alert {
    margin-bottom: 20px;
}

.table-striped th, .table-striped td {
    padding: 10px;
}

.table-bordered th, .table-bordered td {
    border: 1px solid #dee2e6;
    padding: 8px;
    text-align: center;
}

.table-bordered {
    border-collapse: collapse;
    width: 100%;
}
</style>

<div id="container">
    <?php if ($response == 1) { ?>
    <div class="card">
        <div class="card-body">
            <h2>Search Student</h2>
            <form action="" method="POST" class="wufoo leftLabel page1">
                <div class="row">
                    <div class="col-md-4">
                        <label for="exam_roll_no" class="form-label">Exam Roll No <span class="text-danger">*</span></label>
                        <input type="text" id="exam_roll_no" name="exam_roll_no" class="form-control" required>
                    </div>
                    <div class="col-md-12 mt-3">
                        <input type="submit" name="search" value="Search" class="btn btn-primary">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($info) { ?>
    <div class="card">
        <div class="card-body">
            <h2>Edit Student & Paper Info</h2>
            <form action="" method="POST" class="wufoo leftLabel page1">
                <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($info['exam_id']); ?>">
                <input type="hidden" name="form_processed" value="1">
                
                <!-- Student Info -->
                <h3>Student Information</h3>
                <div class="row">
                    <?php
                    foreach ($fields as $field => $type) {
                        $value = htmlspecialchars($field === 'course_name' ? ($info['course_name_display'] ?? '') : ($info[$field] ?? ''));
                        $label = ucwords(str_replace('_', ' ', $field));
                        $input_type = ($type == 'number') ? 'number' : $type;
                        $step = ($type == 'number') ? ' step="any"' : '';
                        ?>
                        <div class="col-md-4 mb-3">
                            <label for="<?php echo $field; ?>" class="form-label"><?php echo $label; ?></label>
                            <input type="<?php echo htmlspecialchars($input_type); ?>" name="<?php echo $field; ?>" id="<?php echo htmlspecialchars($field); ?>" class="form-control" value="<?php echo $value; ?>"<?php echo $step; ?>>
                        </div>
                    <?php } ?>
                </div>

                <!-- Paper Info -->
                <h3>Paper Information</h3>
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Type</th>
                                    <th>Title of Paper</th>
                                    <th>Theory/Practical</th>
                                    <th>PT Marks Max</th>
                                    <th>PT Marks Obtained</th>
                                    <th>Mid Sem Marks Max</th>
                                    <th>Mid Sem Marks Obtained</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $theory_practical_options = ["Theory", "Practical"];

                                if (!empty($paper)) {
                                    $sno = 1;
                                    while ($paper_row = array_shift($paper)) {
                                        ?>
                                        <tr>
                                            <td><?php echo $sno++; ?></td>
                                            <td>
                                                <input type="hidden" name="subject_id[]" value="<?php echo htmlspecialchars($paper_row['subject_id'] ?? ''); ?>">
                                                <input type="text" name="type[]" class="form-control" value="<?php echo htmlspecialchars($paper_row['type'] ?? ''); ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="title_of_paper[]" class="form-control" value="<?php echo htmlspecialchars($paper_row['title_of_paper'] ?? ''); ?>">
                                            </td>
                                            <td>
                                                <select name="theory_practical[]" class="form-control">
                                                    <?php
                                                    foreach ($theory_practical_options as $option) {
                                                        $selected = ($paper_row['theory_practical'] === $option) ? ' selected' : '';
                                                        ?>
                                                        <option value="<?php echo htmlspecialchars($option); ?>"<?php echo $selected; ?>><?php echo htmlspecialchars($option); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="pt_marks_max[]" class="form-control" value="<?php echo htmlspecialchars($paper_row['pt_marks_max'] ?? '0'); ?>" step="any">
                                            </td>
                                            <td>
                                                <input type="number" name="pt_marks_obt[]" class="form-control" value="<?php echo htmlspecialchars($paper_row['pt_marks_obt'] ?? '0'); ?>" step="any">
                                            </td>
                                            <td>
                                                <input type="number" name="mid_sem_marks_max[]" class="form-control" value="<?php echo htmlspecialchars($paper_row['mid_sem_marks_max'] ?? '0'); ?>" step="any">
                                            </td>
                                            <td>
                                                <input type="number" name="mid_sem_marks_obt[]" class="form-control" value="<?php echo htmlspecialchars($paper_row['mid_sem_marks_obt'] ?? '0'); ?>" step="any">
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="8">No paper data available.</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Single Update Button -->
                <div class="row">
                    <div class="col-md-12 mt-3">
                        <input type="submit" name="update_all" value="Update" class="btn btn-primary">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php } ?>
    <?php } elseif ($response == 2) { ?>
    <div class="card">
        <div class="card-body">
            <h2>Updated Student & Paper Information</h2>
            <table class="table table-striped">
                <?php
                foreach ($fields as $field => $type) {
                    $value = $field === 'course_name' ? ($info['course_name_display'] ?? '') : ($info[$field] ?? '');
                    $label = ucwords(str_replace('_', ' ', $field));
                    ?>
                    <tr>
                        <th><?php echo htmlspecialchars($label); ?>:</th>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                <?php
                }
                ?>
            </table>
            <h3>Paper Information</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Type</th>
                        <th>Title of Paper</th>
                        <th>Theory/Practical</th>
                        <th>PT Marks Max</th>
                        <th>PT Marks Obtained</th>
                        <th>Mid Sem Marks Max</th>
                        <th>Mid Sem Marks Obtained</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($paper)) {
                        $sno = 1;
                        while ($paper_row = array_shift($paper)) {
                            ?>
                            <tr>
                                <td><?php echo $sno++; ?></td>
                                <td><?php echo htmlspecialchars($paper_row['type'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($paper_row['title_of_paper'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($paper_row['theory_practical'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($paper_row['pt_marks_max'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($paper_row['pt_marks_obt'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($paper_row['mid_sem_marks_max'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($paper_row['mid_sem_marks_obt'] ?? ''); ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="8">No paper data available.</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <form action="" method="POST">
                <input type="submit" name="back_to_search" value="Back to Search" class="btn btn-primary">
            </form>
        </div>
    </div>
    <?php } ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
page_footer_start();
page_footer_end();
?>