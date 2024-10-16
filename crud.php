<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Si no hay sesión iniciada, redirigir a la página de inicio de sesión
    header("Location: login.php");
    exit;
}

include 'db.php';

// Obtener la instancia de la base de datos
$db = Database::getInstance();
$conexion = $db->getConnection();

// Ruta del archivo CSV donde se almacenará el historial de eliminaciones
$log_file = 'historial_eliminaciones.csv';

// Función para escribir en el archivo CSV
function log_deletion($file, $user, $table_name, $deleted_data) {
    $timestamp = date('Y-m-d H:i:s');
    $data = array_merge([$user, $table_name, $timestamp], $deleted_data);
    
    // Abrir el archivo en modo "append" (agregar al final)
    $file_handle = fopen($file, 'a');

    // Escribir los datos en el archivo CSV
    fputcsv($file_handle, $data);

    // Cerrar el archivo
    fclose($file_handle);
}

// Obtener el nombre de la tabla desde la URL y escaparla correctamente
$table_name = isset($_GET['table']) ? $_GET['table'] : '';
$table_name = $conexion->real_escape_string($table_name);

// Validar el nombre de la tabla
// Obtener la lista de tablas permitidas
$allowed_tables = [];
$result = $conexion->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $allowed_tables[] = $row[0];
}

// Verificar si la tabla es permitida
if (!in_array($table_name, $allowed_tables)) {
    die("Tabla no permitida.");
}

// Función para obtener el nombre de la clave primaria de la tabla
function get_primary_key($conexion, $table_name) {
    $result = $conexion->query("SHOW KEYS FROM `$table_name` WHERE Key_name = 'PRIMARY'");
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['Column_name'];
    }
    return null;
}

$primary_key = get_primary_key($conexion, $table_name);

// Obtener claves foráneas reales para la tabla
function get_foreign_keys($conexion, $table_name) {
    $foreign_keys = [];
    $query = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
              FROM information_schema.KEY_COLUMN_USAGE 
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? 
              AND REFERENCED_TABLE_NAME IS NOT NULL";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('s', $table_name);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $foreign_keys[$row['COLUMN_NAME']] = [
            'referenced_table' => $row['REFERENCED_TABLE_NAME'],
            'referenced_column' => $row['REFERENCED_COLUMN_NAME']
        ];
    }
    return $foreign_keys;
}

$foreign_keys = get_foreign_keys($conexion, $table_name);

// Mostrar la estructura de la tabla
$result = $conexion->query("SHOW COLUMNS FROM `$table_name`");
echo "<h1>CRUD para la tabla: " . htmlspecialchars($table_name) . "</h1>";
echo "<p>Bienvenido, " . htmlspecialchars($_SESSION['email']) . " | <a href='logout.php' onclick='confirmarAccion(event, \"¿Estás seguro de que deseas cerrar sesión?\")'>Cerrar sesión</a></p>";

echo "<h2>Formulario de inserción/actualización</h2>";
echo "<form method='POST' action='crud.php?table=" . urlencode($table_name);

// Si es una actualización, agregamos la acción y el ID
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    echo "&action=update&edit_id=$edit_id'>";
    // Obtener los datos del registro seleccionado para la edición
    $edit_result = $conexion->prepare("SELECT * FROM `$table_name` WHERE `$primary_key` = ?");
    $stmt_type = get_field_type($conexion, $table_name, $primary_key);
    $edit_result->bind_param($stmt_type, $edit_id);
    $edit_result->execute();
    $edit_data = $edit_result->get_result()->fetch_assoc();
} else {
    echo "&action=insert'>";
    $edit_data = [];
}

// Función para generar el <select> dinámico para claves foráneas
function generate_select($conexion, $field, $value, $referenced_table, $referenced_column) {
    // Determinar el campo a mostrar
    $display_field = get_display_field($conexion, $referenced_table);
    if (!$display_field) {
        die("No se pudo determinar el campo a mostrar para $referenced_table");
    }

    echo "<label for='$field'>$field</label>";
    echo "<select name='$field' id='$field'>";
    echo "<option value=''>Selecciona una opción</option>"; // Opción por defecto

    // Consultar la tabla referenciada para obtener los registros
    $query = "SELECT `$referenced_column`, `$display_field` FROM `$referenced_table`";
    $stmt = $conexion->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    // Generar las opciones para el select
    while ($row = $result->fetch_assoc()) {
        $selected = ($value == $row[$referenced_column]) ? 'selected' : '';
        echo "<option value='" . htmlspecialchars($row[$referenced_column]) . "' $selected>" . htmlspecialchars($row[$display_field]) . "</option>";
    }

    echo "</select><br>";
}

function get_display_field($conexion, $table_name) {
    $result = $conexion->query("SHOW COLUMNS FROM `$table_name`");
    while ($row = $result->fetch_assoc()) {
        if (strpos($row['Type'], 'varchar') !== false || strpos($row['Type'], 'char') !== false) {
            return $row['Field'];
        }
    }
    return null;
}

// Función para obtener el tipo de campo para bind_param
function get_field_type($conexion, $table_name, $field_name) {
    $stmt = $conexion->prepare("SHOW FIELDS FROM `$table_name` WHERE Field = ?");
    $stmt->bind_param('s', $field_name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $type = $result['Type'];
    if (strpos($type, 'int') !== false) return 'i';
    if (strpos($type, 'double') !== false || strpos($type, 'float') !== false || strpos($type, 'decimal') !== false) return 'd';
    return 's';
}

// Generar los campos del formulario dinámicamente
$result->data_seek(0); // Reiniciar el puntero del resultado
$field_types = []; // Inicializar array para almacenar tipos de campo
while ($row = $result->fetch_assoc()) {
    $field = $row['Field'];
    $type = $row['Type'];
    $extra = $row['Extra'];

    // Almacenar el tipo de campo
    $field_types[$field] = $type;

    // No mostrar campos auto_increment
    if ($extra != 'auto_increment') {
        $value = isset($edit_data[$field]) ? $edit_data[$field] : ''; // Precargar si es actualización

        // Verificar si el campo es clave foránea
        if (isset($foreign_keys[$field])) {
            // Obtener la tabla y columna referenciada
            $referenced_table = $foreign_keys[$field]['referenced_table'];
            $referenced_column = $foreign_keys[$field]['referenced_column'];
            generate_select($conexion, $field, $value, $referenced_table, $referenced_column);
        } elseif (strpos($type, 'int') !== false && strpos($type, 'tinyint(1)') === false) {
            echo "<label for='$field'>$field</label><input type='number' name='$field' value='" . htmlspecialchars($value) . "' required><br>";
        } elseif (strpos($type, 'varchar') !== false || strpos($type, 'text') !== false || strpos($type, 'char') !== false) {
            // Si es el campo 'password' en la tabla 'users', usar input type='password'
            if ($table_name == 'users' && $field == 'password') {
                echo "<label for='$field'>$field</label><input type='password' name='$field' value='' " . (isset($edit_id) ? '' : 'required') . "><br>";
                if (isset($edit_id)) {
                    echo "<small>Deja el campo vacío si no deseas cambiar la contraseña</small><br>";
                }
            } else {
                echo "<label for='$field'>$field</label><input type='text' name='$field' value='" . htmlspecialchars($value) . "' required><br>";
            }
        } elseif (strpos($type, 'enum') !== false) {
            // Manejo especial para ENUM
            $enum_values = str_replace(["enum(", ")", "'"], "", $type);
            $options = explode(",", $enum_values);
            echo "<label for='$field'>$field</label><select name='$field'>";
            foreach ($options as $option) {
                $selected = $value == $option ? "selected" : "";
                echo "<option value='$option' $selected>$option</option>";
            }
            echo "</select><br>";
        } elseif (strpos($type, 'tinyint(1)') !== false) {
            // Campos booleanos como checkbox
            echo "<label for='$field'>$field</label>";
            // Agregar un campo oculto con valor '0' antes del checkbox
            echo "<input type='hidden' name='$field' value='0'>";
            echo "<input type='checkbox' name='$field' value='1' " . ($value ? "checked" : "") . "><br>";
        } elseif (strpos($type, 'date') !== false) {
            echo "<label for='$field'>$field</label><input type='date' name='$field' value='" . htmlspecialchars($value) . "' required><br>";
        }
    }
}

echo "<input type='submit' value='" . (isset($edit_id) ? 'Actualizar' : 'Guardar') . "'>";
echo "</form>";

// Insertar o actualizar datos usando sentencias preparadas
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_GET['action'] == 'insert') {
        // Procesar inserción
        $columns = [];
        $placeholders = [];
        $values = [];
        $types = '';

        foreach ($_POST as $key => $value) {
            // Si es el campo 'password' en la tabla 'users', aplicar password_hash()
            if ($table_name == 'users' && $key == 'password') {
                $value = password_hash($value, PASSWORD_DEFAULT);
            }

            $columns[] = "`$key`";
            $placeholders[] = '?';
            if ($value === '') {
                $value = null;
            }
            $values[] = $value;
            // Determinar el tipo
            $field_type = get_field_type($conexion, $table_name, $key);
            $types .= $field_type;
        }

        $columns_str = implode(",", $columns);
        $placeholders_str = implode(",", $placeholders);

        $stmt = $conexion->prepare("INSERT INTO `$table_name` ($columns_str) VALUES ($placeholders_str)");
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            echo "Registro insertado correctamente.";
        } else {
            echo "Error en la inserción: " . $stmt->error;
        }

    } elseif ($_GET['action'] == 'update' && isset($_GET['edit_id'])) {
        // Procesar actualización
        $edit_id = intval($_GET['edit_id']);
        $update_parts = [];
        $values = [];
        $types = '';

        foreach ($_POST as $key => $value) {
            // Si es el campo 'password' en la tabla 'users'
            if ($table_name == 'users' && $key == 'password') {
                if ($value != '') {
                    // Si se ingresó una nueva contraseña, aplicamos password_hash() y la actualizamos
                    $value = password_hash($value, PASSWORD_DEFAULT);
                    $update_parts[] = "`$key` = ?";
                    $values[] = $value;
                    $field_type = get_field_type($conexion, $table_name, $key);
                    $types .= $field_type;
                } else {
                    // Si el campo está vacío, no actualizar la contraseña
                    continue;
                }
            } else {
                $update_parts[] = "`$key` = ?";
                if ($value === '') {
                    $value = null;
                }
                $values[] = $value;
                // Determinar el tipo
                $field_type = get_field_type($conexion, $table_name, $key);
                $types .= $field_type;
            }
        }

        $values[] = $edit_id; // Agregamos el ID del registro
        $types .= get_field_type($conexion, $table_name, $primary_key); // Tipo del ID

        $update_str = implode(",", $update_parts);
        $stmt = $conexion->prepare("UPDATE `$table_name` SET $update_str WHERE `$primary_key` = ?");
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            echo "Registro actualizado correctamente.";
        } else {
            echo "Error en la actualización: " . $stmt->error;
        }
    }
}

// Eliminar registros y guardar en la bitácora (CSV)
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $delete_id = $_GET['delete_id'];

    // Primero, obtener los datos del registro antes de eliminarlo
    $query = "SELECT * FROM `$table_name` WHERE `$primary_key` = ?";
    $stmt = $conexion->prepare($query);
    $stmt_type = get_field_type($conexion, $table_name, $primary_key);
    $stmt->bind_param($stmt_type, $delete_id);
    $stmt->execute();
    $deleted_data = $stmt->get_result()->fetch_assoc();

    if ($deleted_data) {
        // Guardar los datos eliminados en la bitácora (CSV)
        $user = $_SESSION['email'];  // Usuario que realiza la acción
        log_deletion($log_file, $user, $table_name, $deleted_data);

        // Proceder a la eliminación del registro
        $stmt = $conexion->prepare("DELETE FROM `$table_name` WHERE `$primary_key` = ?");
        $stmt->bind_param($stmt_type, $delete_id);
        if ($stmt->execute()) {
            echo "Registro eliminado correctamente.";
        } else {
            echo "Error en la eliminación: " . $stmt->error;
        }
    } else {
        echo "Registro no encontrado.";
    }
}

// Implementación de la paginación

// Número de registros por página
$records_per_page = 10;

// Número de página actual (por defecto 1)
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;

// Calcular el OFFSET para la consulta SQL
$offset = ($current_page - 1) * $records_per_page;

// Obtener el número total de registros
$total_records_result = $conexion->query("SELECT COUNT(*) as total FROM `$table_name`");
$total_records_row = $total_records_result->fetch_assoc();
$total_records = $total_records_row['total'];

// Calcular el número total de páginas
$total_pages = ceil($total_records / $records_per_page);

// Modificar la consulta para obtener solo los registros de la página actual
$query = "SELECT * FROM `$table_name` LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param('ii', $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Mostrar los registros de la tabla
echo "<h2>Registros existentes</h2>";

if ($result->num_rows > 0) {
    echo "<table border='1'><tr>";

    // Mostrar los nombres de las columnas
    $fields = $result->fetch_fields();
    $field_types_display = [];
    foreach ($fields as $field) {
        echo "<th>" . htmlspecialchars($field->name) . "</th>";
        $field_types_display[$field->name] = $field->type;
    }
    echo "<th>Acciones</th>";
    echo "</tr>";

    // Mostrar los datos de cada registro
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            $type = $field_types_display[$key];
            if ($type == MYSQLI_TYPE_TINY) { // Tipo booleano
                // Mostrar como icono
                echo "<td>" . ($value ? '✅' : '❌') . "</td>";
            } elseif ($type == MYSQLI_TYPE_BIT) {
                // Mostrar como checkbox
                echo "<td><input type='checkbox' disabled " . ($value ? 'checked' : '') . "></td>";
            } else {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
        }

        // Agregar opciones de actualización, eliminación y vista detallada
        $id = $row[$primary_key]; // Usar la clave primaria identificada
        echo "<td>";
        echo "<a href='crud.php?table=" . urlencode($table_name) . "&view_id=$id'>Ver</a> | ";
        echo "<a href='crud.php?table=" . urlencode($table_name) . "&edit_id=$id'>Editar</a> | ";
        echo "<a href='crud.php?table=" . urlencode($table_name) . "&action=delete&delete_id=$id' onclick='confirmarAccion(event, \"¿Estás seguro de que deseas eliminar este registro?\")'>Eliminar</a>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</table>";

    // Mostrar enlaces de paginación

    echo "<div style='margin-top:20px;'>";
    if ($current_page > 1) {
        echo "<a href='crud.php?table=" . urlencode($table_name) . "&page=1'>Primera</a> ";
        echo "<a href='crud.php?table=" . urlencode($table_name) . "&page=" . ($current_page - 1) . "'>Anterior</a> ";
    }

    // Mostrar enlaces para cada página si el total de páginas es razonable
    if ($total_pages <= 15) {
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $current_page) {
                echo "<strong>$i</strong> ";
            } else {
                echo "<a href='crud.php?table=" . urlencode($table_name) . "&page=$i'>$i</a> ";
            }
        }
    } else {
        // Si hay muchas páginas, mostrar solo las cercanas a la actual
        $start = max(1, $current_page - 5);
        $end = min($total_pages, $current_page + 5);
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $current_page) {
                echo "<strong>$i</strong> ";
            } else {
                echo "<a href='crud.php?table=" . urlencode($table_name) . "&page=$i'>$i</a> ";
            }
        }
    }

    if ($current_page < $total_pages) {
        echo "<a href='crud.php?table=" . urlencode($table_name) . "&page=" . ($current_page + 1) . "'>Siguiente</a> ";
        echo "<a href='crud.php?table=" . urlencode($table_name) . "&page=$total_pages'>Última</a>";
    }
    echo "</div>";

} else {
    echo "No hay registros.";
}

// Mostrar información detallada de un registro específico
if (isset($_GET['view_id'])) {
    $view_id = intval($_GET['view_id']);
    $stmt = $conexion->prepare("SELECT * FROM `$table_name` WHERE `$primary_key` = ?");
    $stmt_type = get_field_type($conexion, $table_name, $primary_key);
    $stmt->bind_param($stmt_type, $view_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $record = $result->fetch_assoc();
        echo "<h2>Detalles del registro</h2>";
        echo "<table border='1'>";
        foreach ($record as $key => $value) {
            $type = $field_types_display[$key];
            echo "<tr>";
            echo "<th>" . htmlspecialchars($key) . "</th>";
            if ($type == MYSQLI_TYPE_TINY) { // Tipo booleano
                // Mostrar como icono
                echo "<td>" . ($value ? '✅' : '❌') . "</td>";
            } elseif ($type == MYSQLI_TYPE_BIT) {
                // Mostrar como checkbox
                echo "<td><input type='checkbox' disabled " . ($value ? 'checked' : '') . "></td>";
            } else {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Registro no encontrado.";
    }
}
?>

<!-- Agregar el script de confirmación -->
<script>
function confirmarAccion(event, mensaje) {
    if (!confirm(mensaje)) {
        event.preventDefault();
    }
}
</script>


