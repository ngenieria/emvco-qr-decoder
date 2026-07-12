<?php

namespace App\Models;

/**
 * EMVParser - Parser genérico para estructuras TLV (Tag-Length-Value)
 * 
 * Interpreta cualquier estructura TLV de forma recursiva, soportando:
 * - Etiquetas anidadas
 * - Templates propietarios (80-99)
 * - Etiquetas múltiples
 * - Valores en hexadecimal
 * 
 * @package App\Models
 * @author ngenieria
 * @version 1.0.0
 */
class EMVParser
{
    /**
     * Payload en formato hexadecimal
     * 
     * @var string
     */
    private string $payload = '';

    /**
     * Posición actual de lectura
     * 
     * @var int
     */
    private int $position = 0;

    /**
     * Diccionario de etiquetas
     * 
     * @var TagDictionary
     */
    private TagDictionary $dictionary;

    /**
     * Etiquetas que contienen templates anidados
     * 
     * @var array
     */
    private array $templateTags = ['26', '27', '28', '62', '64', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89', '90', '91', '92', '93', '94', '95', '96', '97', '98', '99'];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dictionary = new TagDictionary();
    }

    /**
     * Parsear payload QR completo
     * 
     * @param string $payload Payload en hexadecimal
     * @return array Estructura TLV parseada
     * @throws \InvalidArgumentException Si el payload no es válido
     */
    public function parse(string $payload): array
    {
        if (!$this->isValidHex($payload)) {
            throw new \InvalidArgumentException('Payload debe ser hexadecimal válido');
        }

        $this->payload = strtoupper($payload);
        $this->position = 0;

        $result = [];
        
        while ($this->position < strlen($this->payload)) {
            $tag = $this->readTag();
            if ($tag === null) {
                break;
            }

            $length = $this->readLength();
            if ($length === null) {
                break;
            }

            $value = $this->readValue($length);

            $tagInfo = $this->dictionary->getTag($tag);

            $result[$tag] = [
                'tag' => $tag,
                'name' => $tagInfo['name'] ?? "Etiqueta desconocida: $tag",
                'description' => $tagInfo['description'] ?? '',
                'length' => $length,
                'value' => $value,
                'value_hex' => $value,
                'value_ascii' => $this->hexToAscii($value),
                'value_utf8' => $this->hexToUtf8($value),
            ];

            // Si es un template, parsear contenido anidado
            if ($this->isTemplate($tag)) {
                $result[$tag]['templates'] = $this->parseTemplate($value);
            }
        }

        return $result;
    }

    /**
     * Leer una etiqueta (1-4 bytes hexadecimales)
     * 
     * @return string|null Etiqueta en formato hexadecimal
     */
    private function readTag(): ?string
    {
        if ($this->position >= strlen($this->payload)) {
            return null;
        }

        $firstByte = substr($this->payload, $this->position, 2);
        $this->position += 2;

        // Verificar si es etiqueta de 2 bytes
        if (str_ends_with($firstByte, '0') || str_ends_with($firstByte, '1') || str_ends_with($firstByte, '2') || str_ends_with($firstByte, '3')) {
            // Etiqueta de 2 bytes
            if ($this->position < strlen($this->payload)) {
                $secondByte = substr($this->payload, $this->position, 2);
                $this->position += 2;
                return $firstByte . $secondByte;
            }
        }

        return $firstByte;
    }

    /**
     * Leer la longitud (1-3 bytes)
     * 
     * @return int|null Longitud en bytes
     */
    private function readLength(): ?int
    {
        if ($this->position >= strlen($this->payload)) {
            return null;
        }

        $firstByte = hexdec(substr($this->payload, $this->position, 2));
        $this->position += 2;

        if ($firstByte <= 127) {
            return $firstByte;
        }

        // Formato largo (>127)
        $numBytes = $firstByte & 0x7F;
        if ($numBytes === 0 || $numBytes > 3) {
            return null;
        }

        $length = 0;
        for ($i = 0; $i < $numBytes; $i++) {
            $length = ($length << 8) | hexdec(substr($this->payload, $this->position, 2));
            $this->position += 2;
        }

        return $length;
    }

    /**
     * Leer el valor de N bytes
     * 
     * @param int $length Número de bytes a leer
     * @return string Valor en hexadecimal
     */
    private function readValue(int $length): string
    {
        $bytesToRead = $length * 2; // 2 caracteres hex por byte
        
        if ($this->position + $bytesToRead > strlen($this->payload)) {
            $bytesToRead = strlen($this->payload) - $this->position;
        }

        $value = substr($this->payload, $this->position, $bytesToRead);
        $this->position += $bytesToRead;

        return $value;
    }

    /**
     * Verificar si una etiqueta contiene templates anidados
     * 
     * @param string $tag Etiqueta a verificar
     * @return bool
     */
    private function isTemplate(string $tag): bool
    {
        return in_array($tag, $this->templateTags, true);
    }

    /**
     * Parsear template anidado
     * 
     * Redirige a parse() pero sobre el valor del template
     * 
     * @param string $value Valor hexadecimal del template
     * @return array Templates parseados
     */
    private function parseTemplate(string $value): array
    {
        $savedPayload = $this->payload;
        $savedPosition = $this->position;

        $this->payload = $value;
        $this->position = 0;

        $result = [];
        
        while ($this->position < strlen($this->payload)) {
            $tag = $this->readTag();
            if ($tag === null) {
                break;
            }

            $length = $this->readLength();
            if ($length === null) {
                break;
            }

            $val = $this->readValue($length);
            $tagInfo = $this->dictionary->getTag($tag);

            $result[$tag] = [
                'tag' => $tag,
                'name' => $tagInfo['name'] ?? "Etiqueta desconocida: $tag",
                'description' => $tagInfo['description'] ?? '',
                'length' => $length,
                'value' => $val,
                'value_hex' => $val,
                'value_ascii' => $this->hexToAscii($val),
                'value_utf8' => $this->hexToUtf8($val),
            ];

            // Recursión para templates anidados
            if ($this->isTemplate($tag)) {
                $result[$tag]['templates'] = $this->parseTemplate($val);
            }
        }

        $this->payload = $savedPayload;
        $this->position = $savedPosition;

        return $result;
    }

    /**
     * Convertir hexadecimal a ASCII
     * 
     * @param string $hex Cadena hexadecimal
     * @return string Representación ASCII
     */
    private function hexToAscii(string $hex): string
    {
        $result = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $byte = hexdec(substr($hex, $i, 2));
            if ($byte >= 32 && $byte <= 126) {
                $result .= chr($byte);
            } else {
                $result .= '.';
            }
        }
        return $result;
    }

    /**
     * Convertir hexadecimal a UTF-8
     * 
     * @param string $hex Cadena hexadecimal
     * @return string Representación UTF-8
     */
    private function hexToUtf8(string $hex): string
    {
        $bytes = hex2bin($hex);
        return mb_convert_encoding($bytes, 'UTF-8', 'UTF-8');
    }

    /**
     * Validar si una cadena es hexadecimal válida
     * 
     * @param string $hex Cadena a validar
     * @return bool
     */
    private function isValidHex(string $hex): bool
    {
        return !empty($hex) && ctype_xdigit($hex) && strlen($hex) % 2 === 0;
    }
}
