<?php
$env = file_get_contents(__DIR__ . "/.env");
$lines = explode("\n", $env);

foreach ($lines as $line) {
    preg_match("/([^#]+)\=(.*)/", $line, $matches);
    if (isset($matches[2])) {
        putenv(trim($line));
    }
}

$conn = mysqli_connect("localhost", "u200746388_baseprueba", getenv("PASSWORD"), "u200746388_baseprueba");
if ($mysqli -> connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
    exit();
}

$select = $conn->query("SELECT * FROM todos where done=1");

?>

<!doctype html>
<html lang="es">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Aplicación de todo</title>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Aplicación de todo</a>
        </div>
    </nav>

    <main class="mt-4">
        <div class="container">
            <div class="table-responsive">
                <table class="table">
                    <colgroup>
                        <col style="width: 10%;">
                        <col style="width: auto;">
                        <col style="width: 26%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Título del todo</th>
                            <th scope="col">Finalizado el</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $select->fetch_assoc()): ?>
                            <tr>
                                <td scope="row">
                                    <?php echo $row['id'] ?>
                                    <input disabled type="checkbox" readonly <?php echo $row["done"] == "1" ? "checked=\"checked\"" : "" ?>" />
                                </td>
                                <td><?php echo $row['text'] ?></td>
                                <td><?php echo $row['done_time'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="py-3">
                <p class="text-white text-underlined text-center m-0">
                    <a href="/" class="link-primary">Volver</a>
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