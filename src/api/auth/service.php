<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

/**
 * Clase AuthService para manejar operaciones de autenticación de usuarios.
 *
 * Encapsula lógica de inicio de sesión, cierre de sesión y registro usando
 * inyección de dependencias para la conexión de base de datos y servicio JWT.
 * Sigue el principio de responsabilidad única — esta clase solo maneja
 * autenticación, nunca SQL directo fuera de sus propios métodos.
 *
 * @category Authentication
 * @author Carlos Gabriel Magallanes López
 * @license https://opensource.org/licenses/MIT MIT License
 * @since 2026-05-19 Initial version (Version: 1.0)
 * @version 2026-05-19 Initial version (Version: 1.0)
 */
class AuthService {

    /**
     * Conexión PDO a la base de datos intern_platform.
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Instancia del servicio JWT para generación y validación de tokens.
     * @var JWT
     */
    private JWT $jwt;

    /**
     * Constructor para AuthService.
     *
     * Recibe dependencias mediante inyección — AuthService nunca
     * instancia sus propias conexiones de base de datos o JWT.
     *
     * @param Database $db  Instancia de base de datos conectada a intern_platform.
     * @param JWT $jwt Instancia del servicio JWT para operaciones de token.
     * @return void
     */
    public function __construct(Database $db, JWT $jwt) {
        
        // Extraer la conexión PDO sin procesar del contenedor de la base de datos
        $this->pdo = $db->getConnection();
        $this->jwt = $jwt;
    }

    /**
     * Autentica a un usuario y devuelve un token JWT firmado.
     *
     * Busca al usuario por correo electrónico, verifica la contraseña bcrypt,
     * verifica el indicador activo y genera un token al éxito.
     * Devuelve una matriz de resultado estructurada en todos los casos para que el llamador
     * pueda decidir cómo responder (código HTTP, cuerpo JSON, etc.).
     *
     * @param string $email Dirección de correo electrónico del usuario de la solicitud.
     * @param string $password Contraseña en texto plano de la solicitud.
     * @return array{
     *     success: bool,
     *     code: int,
     *     token?: string,
     *     user?: array,
     *     error?: string
     * } Matriz de resultado con código HTTP y token+usuario o mensaje de error.
     */
    public function login(string $email, string $password): array {

        // Obtener al usuario por correo electrónico — incluir password_hash solo aquí
        $sql = 'SELECT id_usuario, nombre, apellido, email, password_hash, rol, activo FROM usuarios WHERE email = ? LIMIT 1';
        $statementt = $this->pdo->prepare($sql);
        $statementt->execute([trim($email)]);
        $user = $statementt->fetch();

        // Usar un error genérico para evitar revelar si el correo electrónico existe
        if (!$user) return $this->fail(401, 'Credenciales incorrectas');

        // Los usuarios inactivos no pueden autenticarse sin importar la contraseña
        if (!$user['activo']) return $this->fail(403, 'Cuenta inactiva. Contacte con soporte.');
    
        // Verificar la contraseña en texto plano contra el hash bcrypt almacenado
        if (!password_verify(trim($password), $user['password_hash'])) {return $this->fail(401, 'Credenciales incorrectas');}

        // Variables locales que facilitan el acceso a los datos del usuario y la generación del token
        $userId = $user['id_usuario'];
        $userEmail = $user['email'];
        $userRole = $user['rol'];
        
        // Buscar empresa requerida para acceder al sistema
        $statementCompany = $this->pdo->prepare(
            'SELECT e.db_name FROM empresas e
            INNER JOIN usuarios u ON u.id_empresa = e.id_empresa
            WHERE u.id_usuario = ? LIMIT 1'
        );
        $statementCompany->execute([$userId]);
        $company = $statementCompany->fetch();

        // Bloquear si no tiene empresa asignada
        if (!$company || empty($company['db_name'])) {
            return $this->fail(403, 'Tu cuenta no está asignada a ninguna empresa. Contacta con soporte.');
        }

        // Extraer el nombre de la base de datos de la empresa para incluirlo en el token
        $db = $company['db_name'];

        // Generar el JWT firmado para esta sesión de usuario
        $token = $this->jwt->generate(id: $userId, email: $userEmail,role: $userRole, name: $user['nombre'], dbName: $db);

        // Devolver éxito — nunca incluir password_hash en la respuesta
        return [
            'success' => true,
            'code' => 200,
            'token' => $token,
            'user' => [
                'id' => $userId,
                'nombre' => $user['nombre'],
                'apellido' => $user['apellido'],
                'email' => $userEmail,
                'rol'=> $userRole,
                'db_name' => $db,
            ]
        ];
    }

    /**
     * Registra un nuevo usuario en la plataforma.
     *
     * Aplica hash a la contraseña con bcrypt antes de almacenarla.
     * Devuelve una matriz de resultado compatible con el contrato login()
     * para que el llamador pueda responder de manera consistente.
     *
     * @param string $name Nombre del nuevo usuario.
     * @param string $surname Apellido del nuevo usuario.
     * @param string $email Dirección de correo electrónico — debe ser única.
     * @param string $password Contraseña en texto plano para aplicar hash y almacenar.
     * @param string $role Rol a asignar (admin | empleado | cliente).
     * @return array{
     *     success: bool,
     *     code: int,
     *     id?: int,
     *     error?: string
     * } Matriz de resultado con el ID del nuevo usuario al éxito o error al fracaso.
     */
    public function register(
        string $name,
        string $surname,
        string $email,
        string $password,
        string $role = 'empleado'
    ): array {

        // Validar que el rol sea uno de los valores permitidos
        try {
            validateAllowedRole($role);
        } catch (InvalidArgumentException $error) {
            return $this->fail(400, $error->getMessage());
        }

        // Aplicar hash a la contraseña con bcrypt antes de persistirla
        $hash = password_hash(trim($password), PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        try {

            // Insertar el nuevo usuario en la base de datos — activo se establece en 1 de forma predeterminada
            $pdo = $this->pdo;
            $sql = 'INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, 1)';
            $statement = $pdo->prepare($sql);
            $statement->execute([trim($name), trim($surname), trim($email), $hash, $role,]);

            // Devolver el ID del nuevo usuario al éxito
            return ['success' => true, 'code' => 201, 'id' => (int) $pdo->lastInsertId(),];

        } catch (PDOException $error) {

            // Correo electrónico duplicado — código de error MySQL 23000 | Conflicto HTTP 409
            if ($error->getCode() === '23000') return $this->fail(409, 'Este correo electrónico ya está registrado.');
            
            // Para cualquier otro error de base de datos, devolver un mensaje de falla genérico | Código 500 Error interno del servidor
            return $this->fail(500, 'Ocurrió un error inesperado. Vuelva a intentarlo más tarde.');

        }
    }

    /**
     * Desactiva una cuenta de usuario configurando activo = 0.
     *
     * Eliminación lógica — el registro nunca se elimina de la base de datos.
     * Solo los administradores deben llamar a este punto final (se aplica a nivel de middleware).
     *
     * @param  int $id ID de usuario a desactivar.
     * @return array Matriz de resultado con bandera de éxito o mensaje de error.
     */
    public function deactivate(int $id): array {

        // Actualizar el indicador activo del usuario a 0 (inactivo)
        $statement = $this->pdo->prepare('UPDATE usuarios SET activo = 0 WHERE id_usuario = ?');
        $statement->execute([$id]);

        // rowCount() devuelve 0 si el usuario no existía o ya estaba inactivo
        if ($statement->rowCount() === 0) return $this->fail(404, 'Usuario no encontrado o ya inactivo.');

        // Devolver éxito si el usuario fue encontrado y desactivado
        return ['success' => true, 'code' => 200];
    }

    /**
     * Construye una matriz de resultado de falla estandarizada.
     *
     * Centraliza la construcción de respuesta de error para que todos los métodos
     * devuelvan la misma forma independientemente del tipo de fallo.
     *
     * @param int $code Código de estado HTTP a sugerir al llamador.
     * @param string $message Descripción de error legible para humanos.
     * @return array Matriz de resultado de falla.
     */
    private function fail(int $code, string $message): array {
        return ['success' => false, 'code' => $code, 'error' => $message,];
    }
}

?>