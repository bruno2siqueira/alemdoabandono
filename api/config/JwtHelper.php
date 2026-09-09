<?php
class JwtHelper
{
    private static function secret(): string
    {
        $arquivo = __DIR__ . '/Secret.php';
        if (is_file($arquivo)) {
            require_once $arquivo;
        }
        if (!defined('JWT_SECRET') || strlen((string) JWT_SECRET) < 32) {
            throw new RuntimeException('A chave JWT não foi configurada corretamente.');
        }
        return (string) JWT_SECRET;
    }

    private static function base64UrlEncode(string $valor): string
    {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $valor): string|false
    {
        $resto = strlen($valor) % 4;
        if ($resto) $valor .= str_repeat('=', 4 - $resto);
        return base64_decode(strtr($valor, '-_', '+/'), true);
    }

    public static function encode(array $payload): string
    {
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $assinatura = hash_hmac('sha256', "{$header}.{$body}", self::secret(), true);
        return "{$header}.{$body}." . self::base64UrlEncode($assinatura);
    }

    public static function decode(string $token): array|false
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) return false;
        [$header, $body, $assinaturaRecebida] = $partes;
        $assinatura = self::base64UrlDecode($assinaturaRecebida);
        $esperada = hash_hmac('sha256', "{$header}.{$body}", self::secret(), true);
        if ($assinatura === false || !hash_equals($esperada, $assinatura)) return false;
        $json = self::base64UrlDecode($body);
        $payload = $json === false ? null : json_decode($json, true);
        if (!is_array($payload)) return false;
        if (isset($payload['exp']) && (int) $payload['exp'] < time()) return false;
        return $payload;
    }
}

