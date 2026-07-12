<?php

namespace App\Models;

/**
 * CRC16 - Validación de CRC16 CCITT FALSE para EMVCo
 * 
 * Implementa el algoritmo CRC16 CCITT FALSE utilizado por EMVCo
 * para validar la integridad de los payloads QR.
 * 
 * Características:
 * - Algoritmo: CRC16 CCITT FALSE
 * - Polinomio: 0x1021
 * - Inicial: 0xFFFF
 * - Entrada: No invertida
 * - Salida: No invertida
 * - XOR final: 0x0000
 * 
 * @package App\Models
 * @author ngenieria
 * @version 1.0.0
 */
class CRC16
{
    /**
     * Polinomio CRC16 CCITT
     * 
     * @var int
     */
    private const POLYNOMIAL = 0x1021;

    /**
     * Valor inicial del CRC
     * 
     * @var int
     */
    private const INITIAL_VALUE = 0xFFFF;

    /**
     * XOR final
     * 
     * @var int
     */
    private const FINAL_XOR = 0x0000;

    /**
     * Calcular CRC16 de un payload
     * 
     * Calcula el CRC16 CCITT FALSE del payload.
     * Para validación EMVCo, excluye los últimos 4 caracteres (etiqueta 63 con CRC).
     * 
     * @param string $payload Payload en formato hexadecimal
     * @return string CRC en formato hexadecimal (4 caracteres)
     * @throws \InvalidArgumentException Si el payload no es válido
     */
    public function calculate(string $payload): string
    {
        if (!$this->isValidHex($payload)) {
            throw new \InvalidArgumentException('Payload debe ser hexadecimal válido');
        }

        // Excluir los últimos 4 caracteres (etiqueta 63 con valor CRC)
        $payloadWithoutCrc = substr($payload, 0, -4);

        $crc = self::INITIAL_VALUE;

        for ($i = 0; $i < strlen($payloadWithoutCrc); $i += 2) {
            $byte = hexdec(substr($payloadWithoutCrc, $i, 2));
            $crc = $this->updateCrc($crc, $byte);
        }

        $crc ^= self::FINAL_XOR;

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Validar CRC de un payload
     * 
     * Compara el CRC calculado con el CRC presente en el payload.
     * 
     * @param string $payload Payload en formato hexadecimal
     * @return bool True si CRC es válido, false si no
     */
    public function validate(string $payload): bool
    {
        try {
            if (!$this->isValidHex($payload) || strlen($payload) < 4) {
                return false;
            }

            // Extraer CRC del payload (últimos 4 caracteres)
            $extractedCrc = substr($payload, -4);

            // Calcular CRC esperado
            $calculatedCrc = $this->calculate($payload);

            return strtoupper($extractedCrc) === $calculatedCrc;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener CRC del payload
     * 
     * Extrae el CRC presente en el payload (últimos 4 caracteres de etiqueta 63).
     * 
     * @param string $payload Payload en formato hexadecimal
     * @return string|null CRC en formato hexadecimal (4 caracteres) o null si no existe
     */
    public function extractCrc(string $payload): ?string
    {
        if (!$this->isValidHex($payload) || strlen($payload) < 4) {
            return null;
        }

        return strtoupper(substr($payload, -4));
    }

    /**
     * Obtener información detallada del CRC
     * 
     * @param string $payload Payload en formato hexadecimal
     * @return array Información del CRC
     */
    public function getInfo(string $payload): array
    {
        $extracted = $this->extractCrc($payload);
        $calculated = null;
        $valid = false;

        try {
            $calculated = $this->calculate($payload);
            $valid = $this->validate($payload);
        } catch (\Exception $e) {
            // CRC no calculable
        }

        return [
            'extracted' => $extracted,
            'calculated' => $calculated,
            'valid' => $valid,
            'status' => $valid ? 'VÁLIDO' : 'INVÁLIDO',
        ];
    }

    /**
     * Actualizar CRC con un byte
     * 
     * Algoritmo shift-XOR para CRC16 CCITT FALSE.
     * 
     * @param int $crc Valor actual del CRC
     * @param int $byte Byte a procesar
     * @return int Nuevo valor del CRC
     */
    private function updateCrc(int $crc, int $byte): int
    {
        $crc ^= ($byte << 8);

        for ($i = 0; $i < 8; $i++) {
            $crc <<= 1;

            if ($crc & 0x10000) {
                $crc ^= self::POLYNOMIAL;
            }

            $crc &= 0xFFFF;
        }

        return $crc;
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
