<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();
session_regenerate_id(true);
require_admin_login('adminlogin.php');

if (isset($_POST['Logout'])) {
    session_destroy();
    redirect_to('index2.html');
}

$message = '';
$allowedSubjects = array('DBMS', 'Programming Languages', 'Science', 'Mathematics', 'General Knowledge', 'Current Affairs');
$editQuestionData = null;

if (isset($_POST['delete_question'])) {
    $deleteQuestionNumber = isset($_POST['delete_question_number']) ? (int)$_POST['delete_question_number'] : 0;

    if ($deleteQuestionNumber <= 0) {
        $message = 'Invalid question selected for deletion.';
    } else {
        $deleteStmt = mysqli_prepare($conn, 'DELETE FROM quiz_question WHERE question_number = ?');
        mysqli_stmt_bind_param($deleteStmt, 'i', $deleteQuestionNumber);
        mysqli_stmt_execute($deleteStmt);
        $deletedCount = mysqli_stmt_affected_rows($deleteStmt);
        mysqli_stmt_close($deleteStmt);

        if ($deletedCount > 0) {
            $message = 'Question deleted successfully.';
        } else {
            $message = 'Question not found for deletion.';
        }
    }
}

if (isset($_POST['load_edit'])) {
    $editQuestionNumber = isset($_POST['edit_question_number']) ? (int)$_POST['edit_question_number'] : 0;

    if ($editQuestionNumber <= 0) {
        $message = 'Invalid question selected for editing.';
    } else {
        $editQuestionStmt = mysqli_prepare($conn, 'SELECT question_number, question_text, subject, a_id FROM quiz_question WHERE question_number = ? LIMIT 1');
        mysqli_stmt_bind_param($editQuestionStmt, 'i', $editQuestionNumber);
        mysqli_stmt_execute($editQuestionStmt);
        $editQuestionResult = mysqli_stmt_get_result($editQuestionStmt);
        $questionRow = mysqli_fetch_assoc($editQuestionResult);
        mysqli_stmt_close($editQuestionStmt);

        if ($questionRow) {
            $choiceMap = array(1 => '', 2 => '', 3 => '');
            $editOptionStmt = mysqli_prepare($conn, 'SELECT a_id, choice FROM options WHERE question_number = ? ORDER BY a_id ASC');
            mysqli_stmt_bind_param($editOptionStmt, 'i', $editQuestionNumber);
            mysqli_stmt_execute($editOptionStmt);
            $editOptionResult = mysqli_stmt_get_result($editOptionStmt);

            while ($optionRow = mysqli_fetch_assoc($editOptionResult)) {
                $optionId = (int)$optionRow['a_id'];
                if (isset($choiceMap[$optionId])) {
                    $choiceMap[$optionId] = $optionRow['choice'];
                }
            }
            mysqli_stmt_close($editOptionStmt);

            $editQuestionData = array(
                'question_number' => (int)$questionRow['question_number'],
                'question_text' => $questionRow['question_text'],
                'subject' => $questionRow['subject'],
                'a_id' => (int)$questionRow['a_id'],
                'choice1' => $choiceMap[1],
                'choice2' => $choiceMap[2],
                'choice3' => $choiceMap[3]
            );
        } else {
            $message = 'Question not found for editing.';
        }
    }
}

if (isset($_POST['update_question'])) {
    $questionNumber = isset($_POST['edit_question_number']) ? (int)$_POST['edit_question_number'] : 0;
    $questionText = trim($_POST['question_text'] ?? '');
    $answerId = isset($_POST['a_id']) ? (int)$_POST['a_id'] : 0;
    $subject = trim($_POST['subject'] ?? '');

    if (!in_array($subject, $allowedSubjects, true)) {
        $subject = 'General Knowledge';
    }

    $choices = array(
        1 => trim($_POST['choice1'] ?? ''),
        2 => trim($_POST['choice2'] ?? ''),
        3 => trim($_POST['choice3'] ?? '')
    );

    if ($questionNumber <= 0 || $questionText === '' || $answerId < 1 || $answerId > 3) {
        $message = 'Please fill valid question details and correct option (1-3).';
    } elseif ($choices[1] === '' || $choices[2] === '' || $choices[3] === '') {
        $message = 'All three choices are required for update.';
    } else {
        mysqli_begin_transaction($conn);
        $isUpdated = true;

        $updateQuestionStmt = mysqli_prepare($conn, 'UPDATE quiz_question SET question_text = ?, subject = ?, a_id = ? WHERE question_number = ?');
        mysqli_stmt_bind_param($updateQuestionStmt, 'ssii', $questionText, $subject, $answerId, $questionNumber);
        if (!mysqli_stmt_execute($updateQuestionStmt)) {
            $isUpdated = false;
        }
        mysqli_stmt_close($updateQuestionStmt);

        if ($isUpdated) {
            $deleteOptionsStmt = mysqli_prepare($conn, 'DELETE FROM options WHERE question_number = ?');
            mysqli_stmt_bind_param($deleteOptionsStmt, 'i', $questionNumber);
            if (!mysqli_stmt_execute($deleteOptionsStmt)) {
                $isUpdated = false;
            }
            mysqli_stmt_close($deleteOptionsStmt);
        }

        if ($isUpdated) {
            $insertOptionStmt = mysqli_prepare($conn, 'INSERT INTO options(question_number, a_id, choice) VALUES (?, ?, ?)');
            foreach ($choices as $choiceId => $choiceText) {
                mysqli_stmt_bind_param($insertOptionStmt, 'iis', $questionNumber, $choiceId, $choiceText);
                if (!mysqli_stmt_execute($insertOptionStmt)) {
                    $isUpdated = false;
                    break;
                }
            }
            mysqli_stmt_close($insertOptionStmt);
        }

        if ($isUpdated) {
            mysqli_commit($conn);
            $message = 'Question updated successfully.';
            $editQuestionData = null;
        } else {
            mysqli_rollback($conn);
            $message = 'Question could not be updated. Please try again.';
        }
    }

    if ($editQuestionData !== null || $message !== 'Question updated successfully.') {
        $editQuestionData = array(
            'question_number' => $questionNumber,
            'question_text' => $questionText,
            'subject' => $subject,
            'a_id' => $answerId,
            'choice1' => $choices[1],
            'choice2' => $choices[2],
            'choice3' => $choices[3]
        );
    }
}

if (isset($_POST['submit'])) {
    $questionNumber = isset($_POST['question_number']) ? (int)$_POST['question_number'] : 0;
    $questionText = trim($_POST['question_text'] ?? '');
    $answerId = isset($_POST['a_id']) ? (int)$_POST['a_id'] : 0;
    $subject = trim($_POST['subject'] ?? '');

    if (!in_array($subject, $allowedSubjects, true)) {
        $subject = 'General Knowledge';
    }

    $choices = array(
        1 => trim($_POST['choice1'] ?? ''),
        2 => trim($_POST['choice2'] ?? ''),
        3 => trim($_POST['choice3'] ?? '')
    );

    if ($questionNumber <= 0 || $questionText === '' || $answerId < 1 || $answerId > 3) {
        $message = 'Please fill valid question number, text, and correct option (1-3).';
    } elseif ($choices[1] === '' || $choices[2] === '' || $choices[3] === '') {
        $message = 'All three choices are required.';
    } else {
        $insertQuestion = mysqli_prepare($conn, 'INSERT INTO quiz_question(question_number, question_text, subject, a_id) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($insertQuestion, 'issi', $questionNumber, $questionText, $subject, $answerId);
        $questionInserted = mysqli_stmt_execute($insertQuestion);
        mysqli_stmt_close($insertQuestion);

        if ($questionInserted) {
            $insertOption = mysqli_prepare($conn, 'INSERT INTO options(question_number, a_id, choice) VALUES (?, ?, ?)');

            foreach ($choices as $choiceId => $choiceText) {
                mysqli_stmt_bind_param($insertOption, 'iis', $questionNumber, $choiceId, $choiceText);
                mysqli_stmt_execute($insertOption);
            }

            mysqli_stmt_close($insertOption);
            $message = 'Question has been added successfully';
        } else {
            $message = 'Question could not be added (question number may already exist).';
        }
    }
}

$next = 1;
$nextResult = mysqli_query($conn, 'SELECT IFNULL(MAX(question_number), 0) + 1 AS next_question FROM quiz_question');
if ($nextResult) {
    $nextRow = mysqli_fetch_assoc($nextResult);
    $next = (int)($nextRow['next_question'] ?? 1);
}

$questionRows = array();
$questionsResult = mysqli_query($conn, "
    SELECT
        q.question_number,
        q.question_text,
        q.subject,
        q.a_id,
        MAX(CASE WHEN o.a_id = 1 THEN o.choice END) AS choice1,
        MAX(CASE WHEN o.a_id = 2 THEN o.choice END) AS choice2,
        MAX(CASE WHEN o.a_id = 3 THEN o.choice END) AS choice3
    FROM quiz_question q
    LEFT JOIN options o ON o.question_number = q.question_number
    GROUP BY q.question_number, q.question_text, q.subject, q.a_id
    ORDER BY q.question_number ASC
");
if ($questionsResult) {
    while ($row = mysqli_fetch_assoc($questionsResult)) {
        $questionRows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Questions | Admin | Quiz Competitors</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="page.css">
</head>

<body class="admin-theme">
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="adminpannel.php">ADMIN PANEL</a>
            <a href="admin_questions.php" class="nav-active">QUESTIONS</a>
            <a href="admin_contacts.php">CONTACTS</a>
            <a href="admin_active_attempts.php">ACTIVE ATTEMPTS</a>
            <a href="admin_payments.php">PAYMENTS</a>
            <a href="index2.html">HOME</a>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="nav-inline-form">
                <button type="submit" name="Logout" class="btnlogin-popup">LOG OUT</button>
            </form>
        </nav>
    </header>

    <main class="admin-panel-wrap">
        <div class="inner-card">
            <h2>Question Management</h2>
            <p class="muted-line">Signed in as <strong><?php echo htmlspecialchars($_SESSION['AdminLoginId']); ?></strong></p>

            <?php if (!empty($message)) { ?>
                <div class="admin-msg"><?php echo htmlspecialchars($message); ?></div>
            <?php } ?>

            <?php if ($editQuestionData !== null) { ?>
                <h3 class="admin-section-title">Edit Question #<?php echo (int)$editQuestionData['question_number']; ?></h3>
                <form method="POST" action="admin_questions.php" class="admin-form-grid admin-form-gap">
                    <input type="hidden" name="edit_question_number" value="<?php echo (int)$editQuestionData['question_number']; ?>">

                    <div class="admin-field">
                        <label>Question Number</label>
                        <input type="number" value="<?php echo (int)$editQuestionData['question_number']; ?>" readonly>
                    </div>

                    <div class="admin-field">
                        <label>Question Text</label>
                        <textarea name="question_text" required><?php echo htmlspecialchars($editQuestionData['question_text']); ?></textarea>
                    </div>

                    <div class="admin-field">
                        <label>Subject</label>
                        <select name="subject" required>
                            <?php foreach ($allowedSubjects as $subjectOption) { ?>
                                <option value="<?php echo htmlspecialchars($subjectOption); ?>" <?php echo ($editQuestionData['subject'] === $subjectOption) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subjectOption); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="admin-field">
                        <label>Choice 1</label>
                        <input type="text" name="choice1" value="<?php echo htmlspecialchars($editQuestionData['choice1']); ?>" required>
                    </div>

                    <div class="admin-field">
                        <label>Choice 2</label>
                        <input type="text" name="choice2" value="<?php echo htmlspecialchars($editQuestionData['choice2']); ?>" required>
                    </div>

                    <div class="admin-field">
                        <label>Choice 3</label>
                        <input type="text" name="choice3" value="<?php echo htmlspecialchars($editQuestionData['choice3']); ?>" required>
                    </div>

                    <div class="admin-field">
                        <label>Correct Option Number (1-3)</label>
                        <input type="number" name="a_id" min="1" max="3" value="<?php echo (int)$editQuestionData['a_id']; ?>" required>
                    </div>

                    <input type="submit" name="update_question" value="Update Question">
                    <a href="admin_questions.php" class="admin-cancel-link">Cancel Edit</a>
                </form>
            <?php } ?>

            <h3 class="admin-section-title">Add New Question</h3>
            <form method="POST" action="admin_questions.php" class="admin-form-grid">
                <div class="admin-field">
                    <label>Question Number</label>
                    <input type="number" name="question_number" value="<?php echo $next; ?>" required>
                </div>

                <div class="admin-field">
                    <label>Question Text</label>
                    <textarea name="question_text" required></textarea>
                </div>

                <div class="admin-field">
                    <label>Subject</label>
                    <select name="subject" required>
                        <?php foreach ($allowedSubjects as $subjectOption) { ?>
                            <option value="<?php echo htmlspecialchars($subjectOption); ?>"><?php echo htmlspecialchars($subjectOption); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="admin-field">
                    <label>Choice 1</label>
                    <input type="text" name="choice1" required>
                </div>

                <div class="admin-field">
                    <label>Choice 2</label>
                    <input type="text" name="choice2" required>
                </div>

                <div class="admin-field">
                    <label>Choice 3</label>
                    <input type="text" name="choice3" required>
                </div>

                <div class="admin-field">
                    <label>Correct Option Number (1-3)</label>
                    <input type="number" name="a_id" min="1" max="3" required>
                </div>

                <input type="submit" name="submit" value="Add Question">
            </form>

            <h3 class="admin-section-title admin-title-top">All Questions</h3>
            <div class="sb-table-wrap">
                <table class="sb-table admin-question-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Subject</th>
                            <th>Answer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($questionRows) === 0) { ?>
                            <tr>
                                <td colspan="5">No questions available.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($questionRows as $row) { ?>
                                <tr>
                                    <td><?php echo (int)$row['question_number']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['question_text']); ?></div>
                                        <div class="admin-choice-preview">
                                            1) <?php echo htmlspecialchars($row['choice1'] ?? ''); ?>
                                            &nbsp;|&nbsp; 2) <?php echo htmlspecialchars($row['choice2'] ?? ''); ?>
                                            &nbsp;|&nbsp; 3) <?php echo htmlspecialchars($row['choice3'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>Option <?php echo (int)$row['a_id']; ?></td>
                                    <td>
                                        <div class="admin-actions-inline">
                                            <form method="POST" action="admin_questions.php">
                                                <input type="hidden" name="edit_question_number" value="<?php echo (int)$row['question_number']; ?>">
                                                <button type="submit" name="load_edit" class="admin-mini-btn">Edit</button>
                                            </form>
                                            <form method="POST" action="admin_questions.php" onsubmit="return confirm('Delete this question?');">
                                                <input type="hidden" name="delete_question_number" value="<?php echo (int)$row['question_number']; ?>">
                                                <button type="submit" name="delete_question" class="admin-mini-btn admin-mini-btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="india-time.js"></script>
</body>

</html>
