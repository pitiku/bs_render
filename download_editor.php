<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar que lleguen los datos
if (!isset($_GET['t'], $_GET['c'], $_GET['pk'], $_GET['id'])) {
    die("Faltan parámetros en la URL (t, c, pk o id).");
}

$host = 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
$user = '2p7TUipr1WHHH3f.root';
$pass = '5ZcNOCkyQA9VGvfL';
$db   = 'Rift';
$dsn = "mysql:host=$host;dbname=$db;port=4000;charset=utf8mb4";

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_CA => '', 
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    $tabla = $_GET['t'];
    $columna = $_GET['c'];
    $id_campo = $_GET['pk']; // Nombre de la columna clave primaria
    $id_valor = $_GET['id'];

    // Consulta específica para el BLOB
    $stmt = $pdo->prepare("SELECT `$columna` FROM `$tabla` WHERE `$id_campo` = ?");
    $stmt->execute([$id_valor]);
    $file = $stmt->fetch();

    if ($file) {
        if($columna == 'saveProgression')
        {
            $filename = "SavedGame_1_progression.sav";
        }
        else if($columna == 'saveRun')
        {
            $filename = "SavedGame_1_run.sav";
        }
        else if($columna == 'save')
        {
            $filename = "save.zip";
        }
        
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        echo $file[$columna];
    }
} catch (Exception $e) { die("Error"); }
?>
