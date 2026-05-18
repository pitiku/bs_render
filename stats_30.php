<?php // Versión de prueba 2

	$host = 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
	$user = '2p7TUipr1WHHH3f.root';
	$pass = '5ZcNOCkyQA9VGvfL';
	$db   = 'BS_30';
	$port = 4000;

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
		$stmt_opciones = $pdo->query("SELECT DISTINCT version FROM B_session ORDER BY version ASC");
		$opciones = $stmt_opciones->fetchAll(PDO::FETCH_COLUMN);

		// 0.1. Capturar el valor seleccionado del formulario
		$filtro_seleccionado = $_GET['filtro'] ?? '';

		// 2. Obtener datos (Limitado a 5000 para que no pete)
		if ($filtro_seleccionado !== '')
		{
			try
			{
				$sql = "SELECT
				(SELECT count(*) from b_session where version = '$version' and id not in (SELECT id_session from z_errorsUnique where version = '$version'))
				/
				(SELECT count(*) from b_session where version = '$version')";

				$stmt = $pdo->query($sql);
				$datos = $stmt->fetchAll();
			}
			catch (PDOException $e)
			{
			}
		}
		else
		{
		}
	}
	catch (PDOException $e)
	{
		die("Error crítico: " . $e->getMessage());
	}
?>

<!DOCTYPE html>
<html lang="es">
	<head>
		<meta charset="UTF-8">
		<title>Stats</title>
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
				</div>

				<div class="buscador">
					<form method="GET" action="">
						<label>Versión:</label>
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
					
					<a href="https://blightstone-production.up.railway.app/views_30.php">Views NEW</a> - 
					<a href="https://blightstone-production.up.railway.app/errorReport_30.php">Error Report NEW</a> - 
					
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
				order: [[ 0, "desc" ]],        
				orderCellsTop: true,
				pageLength: 15,
				language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
				// Añadimos esto para asegurar que use los atributos data-*
				columnDefs: [
				{ targets: '_all', render: function(data, type, row, meta) {
				if (type === 'filter') {
				// Si DataTables pide datos para filtrar, intentamos sacar el data-search
				// Aunque con el atributo en el <td> suele ser suficiente
				return data; 
				}
				return data;
				}}
				]
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
