<?php

require_once "src/database.php";
require_once "src/models/user.php";

$all_users = array();
$active_user = User::get_active_user();
$db = new SMNetProfDatabase();

$query = sprintf("SELECT * FROM %s", User::get_table_name());
$user_rows = $db->execute_query($query);
foreach ($user_rows as $row) {
    $all_users[] = new User($row);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <?php if (count($all_users) > 0): ?>
        <ul id="users-list">
            <?php foreach ($all_users as $user): ?>
                <li class="user-list-item">
                    <a href="view_user.php?id=<?=$user->primary_key?>">
                        <?=$user->username?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="no-data">
            No users.
        </div>
    <?php endif; ?>

    <a href="add_user.php">
        <input id="add-user" type="button" value="Add User" />
    </a>
</body>
</html>
