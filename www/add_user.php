<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/forms.css">
</head>
<body>
    <form class="user-form" action="api/edit_user.php" method="POST">
        <input type="text" name="username" id="username" placeholder="Username" />
        <input type="text" name="display_name" id="display-name" placeholder="Display Name" />
        <input type="password" name="password" id="password" placeholder="Password" />
        <input type="submit" id="new-user-button" value="Create" />
    </form>
</body>
</html>
