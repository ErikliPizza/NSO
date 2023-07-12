<?php

namespace App;

class JWTCodec
{

  public function __construct(private string $key) //set the secret key
  {

  }


  //payload = data from the login.php's payload
  public function encode(array $payload): string //return string
  {
    $header = json_encode([
      "typ" => "JWT",
      "alg" => "HS256"
    ]); //type and algorithm
    $header = $this->base64urlEncode($header); //headeri şifrele

    $payload = json_encode($payload); //access tokeni paketle

    $payload = $this->base64urlEncode($payload); //payloadı şifrele

    $signature = hash_hmac("sha256",
                           $header . "." . $payload,
                           $this->key,
                           true); //imzayı oluştur

    $signature = $this->base64urlEncode($signature); //imzayı şifrele
    return $header . "." . $payload . "." . $signature; //loginde tokeni döndür
  }



  //called from the auth.php on authenticateAcessToken, $token from the auth.php's $matches[1], 0 is the Bearer
  public function decode(string $token): array //return array
  {
    if(preg_match("/^(?<header>.+)\.(?<payload>.+)\.(?<signature>.+)$/", $token, $matches)  !== 1) // noktalardan üçe ayır, eşleşme yoksa
    {
      return false;
    }

    $signature = hash_hmac("sha256",
                           $matches["header"] . "." . $matches["payload"],
                           $this->key,
                           true); //imzayı ayarla

    $signature_from_token = $this->base64urlDecode($matches["signature"]); //tokenden alınan imzayı çözümle

    if(!hash_equals($signature, $signature_from_token)) //oluşturulan imzayla tokendeki imzayı eşleştir, eşleşmezse:
    {
      return false;
    }

    $payload = json_decode($this->base64urlDecode($matches["payload"]), true);

    return $payload;
  }




  private function base64urlEncode(string $text): string
  {
    return str_replace(
      ["+", "/", "="],
      ["-", "_", ""],
      base64_encode($text)
    );
  }




  private function base64urlDecode(string $text): string
  {
    return base64_decode(str_replace(
      ["-", "_"],
      ["+", "/"],
      $text)
    );
  }
}
