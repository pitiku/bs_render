<?php // Versión de prueba 2

$host = 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
$user = '2p7TUipr1WHHH3f.root';
$pass = '5ZcNOCkyQA9VGvfL';
$db   = 'BS_30';
$port = 4000;
$charset = 'utf8mb4';
$tabla  = 'z_errorsUnique'; 
$columna_filtro = 'version'; // La columna por la que quieres filtrar

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_CA => '', 
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];

try {
    // 2. Crear la conexión
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 1. Obtener los valores únicos para el Dropdown
	//$sQuery = "SELECT DISTINCT(version) from b_session
	//								UNION
	//							SELECT DISTINCT(version) from z_errorsUnique";
	$sQuery = "SELECT DISTINCT(version) from z_errorsUnique ORDER BY version desc";
    $stmt_opciones = $pdo->query($sQuery);
    $opciones = $stmt_opciones->fetchAll(PDO::FETCH_COLUMN);

    // 2. Capturar el valor seleccionado del formulario
    $filtro_seleccionado = $_GET['filtro'] ?? '';

    // 3. Preparar la consulta principal con o sin filtro
    if ($filtro_seleccionado !== '') {
        //$sql = "SELECT * FROM $tabla WHERE $columna_filtro = :valor";
        $sql = "select count(*) as total, exception, message, version
                from $tabla
                WHERE $columna_filtro = :valor
                group by exception, message, version
                order by total desc;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['valor' => $filtro_seleccionado]);
    } else {
        //$sql = "SELECT * FROM $tabla";
        $sql = "select count(*) as total, exception, message, version
                from $tabla
                group by exception, message, version
                order by total desc;";
        $stmt = $pdo->query($sql);
    }
    $resultados = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Error Report</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .buscador { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    </style>
</head>
<body>

    <h2>Error Report (<?php echo ucfirst($tabla); ?>)</h2>

    <div class="buscador">
        <form method="GET" action="">
            <label>Filtrar por <?php echo $columna_filtro; ?>:</label>
            <select name="filtro" onchange="this.form.submit()">
                <option value="">-- Todos --</option>
                <?php foreach ($opciones as $opcion): ?>
                    <?php if ($opcion === null) continue; ?>
                    <option value="<?php echo htmlspecialchars($opcion); ?>" 
                        <?php if ($filtro_seleccionado === $opcion) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($opcion); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="?">Limpiar</a>
        </form>
		<a href="https://bs-render.onrender.com/views_30.php">Views NEW</a> - 
		<a href="https://bs-render.onrender.com/errorReport_30.php">Error Report NEW</a> - 
		<a href="https://blightstone-production.up.railway.app">Views OLD</a> - 
		<a href="https://bs-render.onrender.com/errorReport.php">Error Report OLD</a> - 
    </div>

    <?php if ($resultados): ?>
        <table>
            <thead>
                <tr>
                    <?php foreach (array_keys($resultados[0]) as $col): ?>
                        <th><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $valor): ?>
                            <td><?php echo htmlspecialchars($valor ?? 'NULL'); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No se encontraron registros.</p>
    <?php endif; ?>

</body>
</html>
