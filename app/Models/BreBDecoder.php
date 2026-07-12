<?php

namespace App\Models;

/**
 * BreBDecoder - Decoder especializado para QR Bre-B (Brasil/Colombia)
 * 
 * Decodifica QR del sistema Bre-B que utiliza:
 * - GUI: BR.GOV.BCB.BRCODE o CO.GOV.BCB.BRCODE
 * - Estructura específica con subcampos estandarizados
 * 
 * Extrae:
 * - Llave (CPF, CNPJ, Email, Teléfono)
 * - Identificador de red
 * - Tipo de clave
 * - Datos del participante
 * - Datos propietarios
 * - Transaction ID
 * - Todos los subcampos dinámicamente encontrados
 * 
 * @package App\Models
 * @author ngenieria
 * @version 1.0.0
 */
class BreBDecoder
{
    /**
     * Identificadores GUI de Bre-B soportados
     * 
     * @var array
     */
    private array $brebGuiPatterns = [
        'BR.GOV.BCB.BRCODE',
        'CO.GOV.BCB.BRCODE',
        'BR.GOV.BCB',
    ];

    /**
     * Parser EMVCo
     * 
     * @var EMVParser
     */
    private EMVParser $parser;

    /**
     * Diccionario de etiquetas
     * 
     * @var TagDictionary
     */
    private TagDictionary $dictionary;

    /**
     * Diccionario de tipos de clave Bre-B
     * 
     * @var array
     */
    private array $keyTypes = [
        '01' => 'CPF',
        '02' => 'CNPJ',
        '03' => 'Email',
        '04' => 'Teléfono',
        '05' => 'EVP (Random)',
        '06' => 'Otro',
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parser = new EMVParser();
        $this->dictionary = new TagDictionary();
    }

    /**
     * Verificar si un payload pertenece a Bre-B
     * 
     * @param string $payload Payload QR en hexadecimal
     * @return bool
     */
    public function isBreb(string $payload): bool
    {
        try {
            $parsed = $this->parser->parse($payload);

            // Buscar etiqueta 26 (Merchant Account Information)
            if (!isset($parsed['26']['templates'])) {
                return false;
            }

            $templates = $parsed['26']['templates'];

            // Buscar GUI en subcampos
            if (isset($templates['00']['value'])) {
                $gui = $this->hexToString($templates['00']['value']);

                foreach ($this->brebGuiPatterns as $pattern) {
                    if (str_contains($gui, $pattern)) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Decodificar QR Bre-B
     * 
     * @param string $payload Payload QR en hexadecimal
     * @return array Información decodificada
     * @throws \InvalidArgumentException Si no es Bre-B válido
     */
    public function decode(string $payload): array
    {
        if (!$this->isBreb($payload)) {
            throw new \InvalidArgumentException('Este payload no es Bre-B válido');
        }

        $parsed = $this->parser->parse($payload);
        $result = [
            'system' => 'Bre-B',
            'country' => $this->extractCountry($parsed),
            'currency' => $this->extractCurrency($parsed),
            'amount' => $this->extractAmount($parsed),
            'merchant' => $this->extractMerchant($parsed),
            'network' => $this->extractNetwork($parsed),
            'transaction_id' => $this->extractTransactionId($parsed),
            'tags' => $parsed,
        ];

        return $result;
    }

    /**
     * Extraer país
     * 
     * @param array $parsed Tags parseados
     * @return string Código de país (2 caracteres)
     */
    private function extractCountry(array $parsed): string
    {
        if (isset($parsed['58']['value'])) {
            return strtoupper($this->hexToString($parsed['58']['value']));
        }
        return 'CO'; // Default Colombia
    }

    /**
     * Extraer moneda
     * 
     * @param array $parsed Tags parseados
     * @return string Código de moneda (3 dígitos ISO 4217)
     */
    private function extractCurrency(array $parsed): string
    {
        if (isset($parsed['53']['value'])) {
            return $this->hexToString($parsed['53']['value']);
        }
        return '170'; // COP por defecto
    }

    /**
     * Extraer monto
     * 
     * @param array $parsed Tags parseados
     * @return string|null Monto o null si no está presente
     */
    private function extractAmount(array $parsed): ?string
    {
        if (isset($parsed['54']['value'])) {
            $amount = $this->hexToString($parsed['54']['value']);
            return !empty($amount) ? $amount : null;
        }
        return null;
    }

    /**
     * Extraer información del comerciante
     * 
     * @param array $parsed Tags parseados
     * @return array Información del comerciante
     */
    private function extractMerchant(array $parsed): array
    {
        $merchant = [];

        if (isset($parsed['59']['value'])) {
            $merchant['name'] = $this->hexToString($parsed['59']['value']);
        }

        if (isset($parsed['60']['value'])) {
            $merchant['city'] = $this->hexToString($parsed['60']['value']);
        }

        if (isset($parsed['61']['value'])) {
            $merchant['postal_code'] = $this->hexToString($parsed['61']['value']);
        }

        return $merchant;
    }

    /**
     * Extraer información de red Bre-B
     * 
     * @param array $parsed Tags parseados
     * @return array Información de red
     */
    private function extractNetwork(array $parsed): array
    {
        $network = [];

        if (!isset($parsed['26']['templates'])) {
            return $network;
        }

        $templates = $parsed['26']['templates'];

        // GUI
        if (isset($templates['00']['value'])) {
            $network['gui'] = $this->hexToString($templates['00']['value']);
        }

        // Llave (Key)
        if (isset($templates['01']['value'])) {
            $network['key'] = $this->hexToString($templates['01']['value']);
        }

        // Identificador de red
        if (isset($templates['02']['value'])) {
            $network['network_id'] = $this->hexToString($templates['02']['value']);
        }

        // Tipo de clave
        if (isset($templates['03']['value'])) {
            $keyTypeCode = $this->hexToString($templates['03']['value']);
            $network['key_type'] = $this->keyTypes[$keyTypeCode] ?? 'Desconocido';
            $network['key_type_code'] = $keyTypeCode;
        }

        // Datos del participante
        if (isset($templates['04']['value'])) {
            $network['participant_data'] = $this->hexToString($templates['04']['value']);
        }

        // Datos propietarios
        if (isset($templates['05']['value'])) {
            $network['proprietary_data'] = $templates['05']['value'];
        }

        // Referencia
        if (isset($templates['06']['value'])) {
            $network['reference'] = $this->hexToString($templates['06']['value']);
        }

        // Todos los subcampos encontrados dinámicamente
        $network['all_fields'] = [];
        foreach ($templates as $tag => $info) {
            $network['all_fields'][$tag] = [
                'name' => $info['name'] ?? 'Desconocido',
                'value' => $info['value'] ?? '',
                'ascii' => $info['value_ascii'] ?? '',
                'utf8' => $info['value_utf8'] ?? '',
            ];
        }

        return $network;
    }

    /**
     * Extraer Transaction ID
     * 
     * @param array $parsed Tags parseados
     * @return string|null Transaction ID o null si no existe
     */
    private function extractTransactionId(array $parsed): ?string
    {
        if (!isset($parsed['26']['templates'])) {
            return null;
        }

        $templates = $parsed['26']['templates'];

        // Buscar subcampo 07 (Transaction ID)
        if (isset($templates['07']['value'])) {
            return $this->hexToString($templates['07']['value']);
        }

        return null;
    }

    /**
     * Convertir hexadecimal a string
     * 
     * @param string $hex Valor en hexadecimal
     * @return string Valor convertido
     */
    private function hexToString(string $hex): string
    {
        if (empty($hex)) {
            return '';
        }

        $bytes = hex2bin($hex);
        if ($bytes === false) {
            return '';
        }

        // Intentar UTF-8 primero
        $utf8 = mb_convert_encoding($bytes, 'UTF-8', 'UTF-8');
        if (!empty($utf8)) {
            return $utf8;
        }

        // Fallback a ASCII
        return bin2hex($bytes);
    }
}
