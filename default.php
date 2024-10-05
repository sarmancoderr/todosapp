<?php

/* #region INI FILE */
$env = file_get_contents(__DIR__ . "/.env");
$lines = explode("\n", $env);

foreach ($lines as $line) {
    preg_match("/([^#]+)\=(.*)/", $line, $matches);
    if (isset($matches[2])) {
        putenv(trim($line));
    }
}

if (getenv("MODE") !== 'PRODUCTION') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

$conn = mysqli_connect("localhost", getenv("DB_USER"), getenv("DB_PASSWORD"), getenv("DB_NAME"));
if ($conn->connect_errno) {
    echo "Failed to connect to MySQL: " . $conn->connect_error;
    exit();
}

session_start();
if (empty($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['form_token'];
/* #endregion */

$message = "";
$type = "";

$validToken = isset($_POST['form_token']) && $_POST['form_token'] === $_SESSION['form_token'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($validToken) {
        /* #region PROCESS POST */
        $action = $_POST["action"];

        switch ($action) {
            case 'toggle':
                $todoId = $_POST["todoId"];
                $newval = $_POST["todoNewval"];
                $conn->query("UPDATE todos SET done=$newval, done_time=current_timestamp() where id=$todoId");
                break;

            case 'delete':
                $todoId = $_POST["todoId"];
                $conn->query("DELETE FROM todos where id=$todoId");
                $message = "El todo ha sido eliminado correctamente";
                $type = "success";
                break;

            case 'edit':
                $todoId = $_POST["todoId"];
                $title = $_POST["title"];
                $conn->query("UPDATE todos SET text='$title' WHERE id=$todoId");
                $message = "El todo ha sido actualizado correctamente";
                $type = "success";
                break;

            default:
                $text = $_POST["title"];
                if (empty($text)) {
                    $message = "El texto del todo no puede estar vacio";
                    $type = "danger";
                    break;
                }
                $conn->query("INSERT INTO todos (`text`) VALUES ('$text')");
                $message = "El todo ha sido creado correctamente";
                $type = "success";
                break;
        }
        /* #endregion */
    } else {
        $message = "Peticion no válida";
        $type = "danger";
    }

    $_SESSION['form_token'] = bin2hex(random_bytes(32));
    $token = $_SESSION['form_token'];
}

$select = $conn->query("SELECT * FROM todos where done=0");
$countDone = $conn->query("SELECT count(*) as total FROM todos where done=1")->fetch_row();

?>

<!doctype html>
<html lang="es">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        /* Aplica esto a las columnas que quieres que se ajusten */
        .auto-width {
            white-space: nowrap;
        }
    </style>

    <title>Aplicación de todo</title>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Aplicación de todo</a>
        </div>
    </nav>

    <main class="mt-4">
        <div class="container">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="form_token" value="<?php echo $token; ?>">
                <label for="title-todo" class="form-label">Título</label>
                <input type="text" id="title-todo" class="form-control" name="title" aria-describedby="title-todo-help">
                <div id="title-todo-help" class="form-text">
                    Texto del todo
                </div>
            </form>
            <hr />
            <?php if ($select->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <colgroup>
                            <col style="width: 1%;">
                            <col style="width: auto;">
                            <col style="width: 20%;">
                            <col style="width: 1%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="auto-width" scope="col">#</th>
                                <th scope="col">Título del todo</th>
                                <th scope="col">Fecha de creación</th>
                                <th class="auto-width" width="auto" scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $select->fetch_assoc()): ?>
                                <tr>
                                    <td class="auto-width" scope="row">
                                        <?php echo $row['id'] ?>
                                        <input type="checkbox" disabled <?php echo $row["done"] == "1" ? "checked=\"checked\"" : "" ?>" />
                                    </td>
                                    <td><?php echo $row['text'] ?></td>
                                    <td><?php echo $row['created_time'] ?></td>
                                    <td class="auto-width">
                                        <div class="gap-3 d-inline-flex">
                                            <form method="post">
                                                <input type="hidden" name="form_token" value="<?php echo $token; ?>">
                                                <input type="hidden" name="action" value="toggle" />
                                                <input type="hidden" name="todoId" value="<?php echo $row['id'] ?>" />
                                                <input type="hidden" name="todoNewval" value="<?php echo $row["done"] === "1" ? "0" : "1" ?>" />
                                                <button class="btn btn-secondary btn-sm" type="submit">Alternar</button>
                                            </form>

                                            <!-- Button trigger modal -->
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editTodo<?php echo $row["id"]; ?>">
                                                Editar
                                            </button>

                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#removeTodo">
                                                Eliminar
                                            </button>

                                            <!-- Modal -->
                                            <form method="post" class="modal fade" id="removeTodo" tabindex="-1" aria-labelledby="removeTodoLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="removeTodoLabel">
                                                                Eliminar todo <?php echo $row["text"] ?>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Esta acción es <strong>irreversible</strong> ¿Estas seguro?</p>
                                                            <input type="hidden" name="form_token" value="<?php echo $token; ?>">
                                                            <input type="hidden" name="action" value="delete" />
                                                            <input type="hidden" name="todoId" value="<?php echo $row['id'] ?>" />
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>

                                            <!-- Modal -->
                                            <form method="post" class="modal fade" id="editTodo<?php echo $row["id"]; ?>" tabindex="-1" aria-labelledby="editTodo<?php echo $row["id"]; ?>Label" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editTodo<?php echo $row["id"]; ?>Label">Editar todo <?php echo $row["id"]; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="form_token" value="<?php echo $token; ?>">
                                                            <input type="hidden" name="action" value="edit" />
                                                            <input type="hidden" name="todoId" value="<?php echo $row['id'] ?>" />

                                                            <label for="new-title-todo" class="form-label">Título</label>
                                                            <input type="text" id="new-title-todo" class="form-control" name="title" aria-describedby="new-title-todo-help">
                                                            <div id="new-title-todo-help" class="form-text">
                                                                Nuevo texto del todo
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    No has creado ningun todo
                </div>
            <?php endif; ?>
            <div class="py-3">
                <p class="text-center m-0">
                    <?php if ($countDone[0][0] > 0): ?>
                        <a href="/done" class="link-primary">Hay <?php echo $countDone[0][0] ?> todos hechos</a>
                    <?php else: ?>
                        No hay ningun todo hecho
                    <?php endif ?>
                </p>
            </div>
        </div>
    </main>

    <!-- Optional JavaScript; choose one of the two! -->


    <!-- Option 2: Separate Popper and Bootstrap JS -->

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

</body>

</html>