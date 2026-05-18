<?php // Versión de prueba 2

$host = 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
$user = '2p7TUipr1WHHH3f.root';
$pass = '5ZcNOCkyQA9VGvfL';
$db   = 'BS';
$port = 4000;
$tabla  = 'z_error'; 
$columna_filtro = 'version'; // La columna por la que quieres filtrar

try {
    $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_CA => '', 
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 0. Obtener las versiones para el filtro
    $stmt_opciones = $pdo->query("SELECT DISTINCT $columna_filtro FROM $tabla ORDER BY $columna_filtro ASC");
    $opciones = $stmt_opciones->fetchAll(PDO::FETCH_COLUMN);

    // 0.1. Capturar el valor seleccionado del formulario
    $filtro_seleccionado = $_GET['filtro'] ?? '';

    // 1. Listado de tablas para el Dropdown
    $tablas_query = $pdo->query("SHOW TABLES");
    $todas_las_tablas = $tablas_query->fetchAll(PDO::FETCH_COLUMN);
    $tabla_actual = $_GET['t'] ?? ($todas_las_tablas[0] ?? '');

    if (!$tabla_actual) die("No hay tablas en la base de datos.");

    // 2. Obtener datos (Limitado a 5000 para que no pete)
	if ($filtro_seleccionado !== '')
	{
		$stmt = $pdo->query("SELECT * FROM `$tabla_actual` 
			WHERE id_session in (select id from BS.B_session where version = '$filtro_seleccionado') 
			ORDER BY id DESC LIMIT 5000");
	}
	else
	{
		$stmt = $pdo->query("SELECT * FROM `$tabla_actual` ORDER BY id DESC LIMIT 5000");
	}
    $datos = $stmt->fetchAll();
      
    $columnas = !empty($datos) ? array_keys($datos[0]) : [];
    $pk_name = $columnas[0] ?? 'id'; // Asumimos que la primera col es la ID

} catch (PDOException $e) {
    die("Error crítico: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Analytics Views</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; padding: 20px; font-size: 0.85rem; }
        .card { border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .clickable-cell { cursor: pointer; color: #212529; }
        .clickable-cell:hover { background: #e9ecef; border-radius: 4px; }
        .filters input { width: 100%; font-size: 0.75rem; padding: 2px 5px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="m-0 text-primary">Analytics Views</h4>
			<!--
            <form method="GET" class="d-flex gap-2">
                <select name="t" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 250px;">
                    <?php foreach ($todas_las_tablas as $t): ?>
                        <option value="<?= $t ?>" <?= $t == $tabla_actual ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
			-->
        </div>

		<div class="buscador">
			<form method="GET" action="">
				<label>Tabla: </label>
                <select name="t" onchange="this.form.submit()" style="inline-block;">
                    <?php foreach ($todas_las_tablas as $t): ?>
                        <option value="<?= $t ?>" <?= $t == $tabla_actual ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
				<label>		Versión:</label>
				<select name="filtro" onchange="this.form.submit()" style="inline-block">
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
			<a href="https://blightstone-production.up.railway.app/errorReport.php">Error Report</a>	
		</div>

        <div class="table-responsive">
            <table id="mainTable" class="table table-hover table-bordered w-100">
                <thead class="table-dark">
                    <tr>
                        <?php foreach ($columnas as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="filters">
                        <?php foreach ($columnas as $col): ?>
                            <td><input type="text" placeholder="Filtrar <?= $col ?>"></td>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $fila): ?>
                        <tr>
                            <?php foreach ($fila as $col_name => $valor): ?>
                                <td>
                                    <?php if (stripos($col_name, 'save') !== false && !empty($valor)): ?>
                                        <a href="download.php?t=<?= urlencode($tabla_actual) ?>&c=<?= urlencode($col_name) ?>&pk=<?= urlencode($pk_name) ?>&id=<?= urlencode($fila[$pk_name]) ?>" 
                                           class="btn btn-primary btn-sm px-2 py-0">📥 Descargar</a>
                                    <?php else: ?>
                                        <div class="clickable-cell" 
                                             data-fulltext="<?= htmlspecialchars($valor ?? '') ?>" 
                                             data-colname="<?= htmlspecialchars($col_name) ?>"
                                             onclick="showModal(this)">
                                            <?= htmlspecialchars(strlen($valor ?? '') > 40 ? substr($valor, 0, 40) . '...' : ($valor ?? '')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="textModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modalTitle">Detalle de campo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="modalBody" style="white-space: pre-wrap; word-wrap: break-word; background: #f8f9fa; padding: 15px;"></pre>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#mainTable').DataTable({
        orderCellsTop: true,
        pageLength: 15,
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Filtros por columna
    $('#mainTable .filters input').on('keyup change', function() {
        var index = $(this).parent().index();
        table.column(index).search(this.value).draw();
    });
});

function showModal(el) {
    const text = $(el).data('fulltext');
    const col = $(el).data('colname');
    $('#modalTitle').text("Columna: " + col);
    $('#modalBody').text(text || "NULL");
    new bootstrap.Modal(document.getElementById('textModal')).show();
}
</script>

</body>
</html>
