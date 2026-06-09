<?php

// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

/**
 * Clase JWT para generar y validar tokens web JSON.
 *
 * Implements HS256 (HMAC-SHA256) signing without external dependencies.
 * Tokens carry the user identity by the router to
 * enforce access control on every protected endpoint.
 *
 * @category Authentication
 * @author Carlos Gabriel Magallanes López
 * @license https://opensource.org/licenses/MIT MIT License
 * @since 2026-05-19 Initial version (Version: 1.0)
 * @version 2026-05-19 Initial version (Version: 1.0)
 */
class JWT {

    /**
     * Clave secreta utilizada para firmar y verificar tokens.
     * @var string
     */
    private string $secret;

    /**
     * Constructor para la clase JWT.
     *
     * @param string $secret Clave secreta para firmar tokens. Debe tener al menos 32 caracteres.
     * @return void
     *
     * @throws InvalidArgumentException Si la clave secreta es demasiado corta.
     */
    public function __construct(string $secret) {

        // Validar la longitud de la clave secreta (se recomienda al menos 32 caracteres para HS256)
        validateSecretTokenKey($secret);
        $this->secret = $secret;

    }

    /**
     * Genera un token JWT firmado para la carga de usuario dada.
     *
     * El token contiene: ID de usuario, correo electrónico, fecha de emisión y expiración.
     * Se firma con HMAC-SHA256 usando la clave secreta configurada.
     *
     * @param int $id ID de usuario de la base de datos.
     * @param string $email Dirección de correo electrónico del usuario.
     * @param string $name Nombre del usuario.
     * @param string $dbName Nombre de la base de datos del usuario.
     * @param string $companyName Nombre de la empresa del usuario.
     * @return string Cadena JWT firmada en formato header.payload.signature.
     */
    public function generate(int $id, string $email, string $name, string $dbName, string $companyName): string {

        // Construir el encabezado JWT estándar
        $header = $this->base64url(json_encode([
            'alg' => SIGN_ALGORITHM,
            'typ' => 'JWT'
        ]));

        // Construir la carga con reclamos de identidad de usuario y expiración
        $payload = $this->base64url(json_encode([
            'sub' => $id,                                                                        // Asunto
            'email' => $email,
            'name' => $name,
            'db' => $dbName,    
            'empresa' => $companyName,                                                                
            'iat' => time(),                                                                     // Emitido en
            'exp' => time() + TOKEN_EXPIRATION,
        ]));

        // Firmar la cadena header.payload y devolver el token completo
        $signature = $this->sign("$header.$payload");

        // Devolver el token en formato estándar: header.payload.signature
        return "$header.$payload.$signature";
    }

    /**
     * Valida un token JWT y devuelve su carga decodificada.
     *
     * Verifica la firma y comprueba que el token no ha expirado.
     * Devuelve nulo si el token es inválido, manipulado o expirado.
     *
     * @param string $token Cadena JWT en formato header.payload.signature.
     * @return array | null Carga decodificada como matriz asociativa, o nulo si es inválido.
     */
    public function validate(string $token): array | null {

        // Un JWT válido debe tener exactamente tres partes separadas por puntos
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        // Asignar las partes a variables para mayor claridad
        [$header, $payload, $signature] = $parts;

        // Rechazar el token si la firma no coincide
        if (!hash_equals($this->sign("$header.$payload"), $signature)) return null;

        // Decodificar la carga y verificar el reclamo de expiración
        $data = json_decode($this->base64urlDecode($payload), true);
        if (!is_array($data) || !isset($data['exp'])) return null;

        // Rechazar tokens expirados
        if (time() > $data['exp']) return null;

        // Devolver la carga decodificada si el token es válido
        return $data;
    }

    /**
     * Firma una cadena usando HMAC-SHA256 y devuelve una firma codificada en Base64URL.
     *
     * @param string $data Cadena a firmar (header.payload).
     * @return string Firma HMAC codificada en Base64URL.
     */
    private function sign(string $data): string {
        return $this->base64url(
            hash_hmac(HASH_ALGORITHM, $data, key: $this->secret, binary: true)
        );
    }

    /**
     * Codifica una cadena al formato Base64URL (RFC 4648).
     *
     * Reemplaza + con -, / con _, y elimina el relleno = final.
     *
     * @param string $data Cadena sin procesar a codificar.
     * @return string Cadena codificada en Base64URL.
     */
    private function base64url(string $data): string {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'), '='                                           // Eliminar '=' y reemplazar '+' y '/' con caracteres seguros para URL
        );                                
    }

    /**
     * Decodifica una cadena codificada en Base64URL a su valor original.
     *
     * @param string $data Cadena codificada en Base64URL.
     * @return string Cadena sin procesar decodificada.
     */
    private function base64urlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
}

?>