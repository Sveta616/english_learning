<?php

class JWT
{
  private static $secret;

  public static function init($secret)
  {
    self::$secret = $secret;
  }

  public static function encode($payload)
  {
    if (!self::$secret) {
      throw new Exception("JWT secret not initialized. Call JWT::init() first.");
    }

    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = array_merge($payload, [
      'iat' => time(),
      'exp' => time() + (60 * 60 * 24) // 24 часа
    ]);

    $base64UrlHeader = self::base64UrlEncode($header);
    $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
    $base64UrlSignature = self::base64UrlEncode($signature);

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
  }

  public static function decode($token)
  {
    if (!self::$secret) {
      throw new Exception("JWT secret not initialized. Call JWT::init() first.");
    }

    $parts = explode('.', $token);

    if (count($parts) != 3) {
      throw new Exception("Некорректный формат токена");
    }

    list($header, $payload, $signature) = $parts;

    $validSignature = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
    $validSignatureBase64 = self::base64UrlEncode($validSignature);

    if (!hash_equals($signature, $validSignatureBase64)) {
      throw new Exception("Неверная подпись токена");
    }

    $decodedPayload = json_decode(self::base64UrlDecode($payload), true);

    if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
      throw new Exception("Срок действия токена истек");
    }

    return $decodedPayload;
  }

  public static function validate($token)
  {
    try {
      self::decode($token);
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  private static function base64UrlEncode($data)
  {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  private static function base64UrlDecode($data)
  {
    return base64_decode(strtr($data, '-_', '+/'));
  }
}
?>