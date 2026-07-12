<?php

namespace App\Models;

/**
 * TagDictionary - Diccionario completo de etiquetas EMVCo
 * 
 * Contiene la información de todas las etiquetas estándar del especificación
 * EMVCo Merchant Presented Mode QR Code Specification.
 * 
 * Soporta:
 * - Etiquetas estándar (00-64)
 * - Plantillas propietarias (80-99)
 * - Información extendida de cada etiqueta
 * - Búsqueda y recuperación rápida
 * 
 * @package App\Models
 * @author ngenieria
 * @version 1.0.0
 */
class TagDictionary
{
    /**
     * Diccionario de etiquetas EMVCo
     * 
     * @var array
     */
    private array $tags = [
        // Etiquetas de nivel superior
        '00' => [
            'name' => 'Payload Format Indicator',
            'description' => 'Indicador de formato del payload. Identifica el formato y versión del QR.',
            'length' => 2,
            'format' => 'Numeric',
            'range' => ['01'],
        ],
        '01' => [
            'name' => 'Point of Initiation Method',
            'description' => 'Indica si la transacción es estática (11) o dinámica (12).',
            'length' => 2,
            'format' => 'Numeric',
            'range' => ['11', '12'],
        ],
        '02' => [
            'name' => 'Merchant Account Information - Template 02',
            'description' => 'Información de cuenta del comerciante (reservada).',
            'length' => 'Variable',
            'format' => 'Template',
        ],
        '26' => [
            'name' => 'Merchant Account Information',
            'description' => 'Template que contiene GUI y subcampos de información de cuenta del comerciante.',
            'length' => 'Variable',
            'format' => 'Template',
        ],
        '27' => [
            'name' => 'Merchant Account Information - Template 27',
            'description' => 'Información adicional de cuenta del comerciante.',
            'length' => 'Variable',
            'format' => 'Template',
        ],
        '28' => [
            'name' => 'Merchant Account Information - Template 28',
            'description' => 'Información adicional de cuenta del comerciante.',
            'length' => 'Variable',
            'format' => 'Template',
        ],
        '52' => [
            'name' => 'Merchant Category Code',
            'description' => 'Código de categoría del comercio (4 dígitos, ISO 18245).',
            'length' => 4,
            'format' => 'Numeric',
            'example' => '5411 - Computadoras',
        ],
        '53' => [
            'name' => 'Transaction Currency',
            'description' => 'Código numérico de la moneda (ISO 4217). Ej: 840 (USD), 170 (COP).',
            'length' => 3,
            'format' => 'Numeric',
            'example' => '170 para COP (Colombia)',
        ],
        '54' => [
            'name' => 'Transaction Amount',
            'description' => 'Monto de la transacción. Puede estar vacío para transacciones dinámicas.',
            'length' => 'Variable',
            'format' => 'Numeric',
        ],
        '55' => [
            'name' => 'Tip Indicator',
            'description' => 'Indicador de propina. Valores: 01 (fija), 02 (porcentaje), 03 (ambas).',
            'length' => 2,
            'format' => 'Numeric',
            'range' => ['01', '02', '03'],
        ],
        '56' => [
            'name' => 'Convenience Fee Fixed',
            'description' => 'Tarifa de conveniencia fija.',
            'length' => 'Variable',
            'format' => 'Numeric',
        ],
        '57' => [
            'name' => 'Convenience Fee Percentage',
            'description' => 'Tarifa de conveniencia como porcentaje.',
            'length' => 'Variable',
            'format' => 'Numeric',
        ],
        '58' => [
            'name' => 'Country Code',
            'description' => 'Código de país de dos letras (ISO 3166-1 alpha-2). Ej: CO para Colombia.',
            'length' => 2,
            'format' => 'Alphabetic',
            'example' => 'CO para Colombia',
        ],
        '59' => [
            'name' => 'Merchant Name',
            'description' => 'Nombre del comerciante o establecimiento.',
            'length' => 'Variable',
            'format' => 'Text (hasta 25 caracteres)',
        ],
        '60' => [
            'name' => 'Merchant City',
            'description' => 'Ciudad del comerciante.',
            'length' => 'Variable',
            'format' => 'Text (hasta 15 caracteres)',
        ],
        '61' => [
            'name' => 'Postal Code',
            'description' => 'Código postal del comerciante.',
            'length' => 'Variable',
            'format' => 'Text (hasta 10 caracteres)',
        ],
        '62' => [
            'name' => 'Additional Data Field Template',
            'description' => 'Template para datos adicionales.',
            'length' => 'Variable',
            'format' => 'Template',
        ],
        '63' => [
            'name' => 'CRC',
            'description' => 'Checksum CRC16 CCITT FALSE del payload (excluyendo este campo).',
            'length' => 4,
            'format' => 'Hex (4 caracteres)',
        ],
        '64' => [
            'name' => 'Merchant Information',
            'description' => 'Template para información extendida del comerciante.',
            'length' => 'Variable',
            'format' => 'Template',
        ],

        // Subcampos de Merchant Account Information (26)
        '00' => [
            'name' => 'Globally Unique Identifier (GUI)',
            'description' => 'Identificador único global que identifica el sistema de pago. Ej: BR.GOV.BCB.BRCODE para Bre-B Brasil.',
            'length' => 'Variable',
            'format' => 'Text',
            'parent' => '26',
        ],
        '01' => [
            'name' => 'Key',
            'description' => 'Clave principal para el sistema de pago (CPF, CNPJ, email, teléfono, etc).',
            'length' => 'Variable',
            'format' => 'Text',
            'parent' => '26',
        ],
        '02' => [
            'name' => 'Network Identifier',
            'description' => 'Identificador de red o sistema dentro del GUI.',
            'length' => 'Variable',
            'format' => 'Text/Numeric',
            'parent' => '26',
        ],
        '03' => [
            'name' => 'Key Type',
            'description' => 'Tipo de clave: 01=CPF, 02=CNPJ, 03=Email, 04=Teléfono, 05=EVP, 06=Otro.',
            'length' => 2,
            'format' => 'Numeric',
            'parent' => '26',
        ],
        '04' => [
            'name' => 'Participant Data',
            'description' => 'Datos del participante en el sistema.',
            'length' => 'Variable',
            'format' => 'Text',
            'parent' => '26',
        ],
        '05' => [
            'name' => 'Proprietary Data',
            'description' => 'Datos propietarios específicos del sistema.',
            'length' => 'Variable',
            'format' => 'Binary',
            'parent' => '26',
        ],
        '06' => [
            'name' => 'Reference',
            'description' => 'Referencia o ID de transacción.',
            'length' => 'Variable',
            'format' => 'Text/Numeric',
            'parent' => '26',
        ],
        '07' => [
            'name' => 'Transaction ID',
            'description' => 'Identificador único de la transacción.',
            'length' => 'Variable',
            'format' => 'Text/Numeric',
            'parent' => '26',
        ],
        '08' => [
            'name' => 'Private Fields',
            'description' => 'Campos privados del sistema.',
            'length' => 'Variable',
            'format' => 'Binary',
            'parent' => '26',
        ],
        '09' => [
            'name' => 'RFU Fields',
            'description' => 'Campos reservados para uso futuro (RFU).',
            'length' => 'Variable',
            'format' => 'Binary',
            'parent' => '26',
        ],

        // Subcampos de Additional Data (62)
        '01' => [
            'name' => 'Bill Number',
            'description' => 'Número de factura o recibo.',
            'length' => 'Variable',
            'format' => 'Numeric (hasta 6 dígitos)',
            'parent' => '62',
        ],
        '02' => [
            'name' => 'Mobile Number',
            'description' => 'Número de teléfono móvil.',
            'length' => 'Variable',
            'format' => 'Numeric',
            'parent' => '62',
        ],
        '03' => [
            'name' => 'Store Label',
            'description' => 'Etiqueta o identificador de tienda.',
            'length' => 'Variable',
            'format' => 'Text (hasta 20 caracteres)',
            'parent' => '62',
        ],
        '04' => [
            'name' => 'Loyalty Number',
            'description' => 'Número de programa de fidelización.',
            'length' => 'Variable',
            'format' => 'Text (hasta 20 caracteres)',
            'parent' => '62',
        ],
        '05' => [
            'name' => 'Reference Label',
            'description' => 'Etiqueta de referencia personalizada.',
            'length' => 'Variable',
            'format' => 'Text (hasta 25 caracteres)',
            'parent' => '62',
        ],
        '06' => [
            'name' => 'Terminal Label',
            'description' => 'Identificador del terminal POS.',
            'length' => 'Variable',
            'format' => 'Text (hasta 10 caracteres)',
            'parent' => '62',
        ],
        '07' => [
            'name' => 'Purpose of Transaction',
            'description' => 'Descripción del propósito de la transacción.',
            'length' => 'Variable',
            'format' => 'Text (hasta 40 caracteres)',
            'parent' => '62',
        ],
        '08' => [
            'name' => 'Additional Consumer Data Request Indicator',
            'description' => 'Indicador de solicitud de datos adicionales del consumidor.',
            'length' => 2,
            'format' => 'Numeric',
            'parent' => '62',
        ],
    ];

    /**
     * Obtener información de una etiqueta
     * 
     * @param string $tag Etiqueta a buscar
     * @return array Información de la etiqueta o etiqueta desconocida
     */
    public function getTag(string $tag): array
    {
        if (isset($this->tags[$tag])) {
            return $this->tags[$tag];
        }

        // Etiquetas propietarias (80-99)
        if (ctype_xdigit($tag) && strlen($tag) === 2) {
            $hex = hexdec($tag);
            if ($hex >= 0x80 && $hex <= 0x99) {
                return [
                    'name' => 'Proprietary Template ' . $tag,
                    'description' => 'Template propietario para sistemas de pago personalizados.',
                    'length' => 'Variable',
                    'format' => 'Template',
                    'proprietary' => true,
                ];
            }
        }

        return [
            'name' => 'Unknown Tag',
            'description' => "Etiqueta no registrada: $tag",
            'length' => 'Variable',
            'format' => 'Unknown',
        ];
    }

    /**
     * Obtener todas las etiquetas registradas
     * 
     * @return array
     */
    public function getAllTags(): array
    {
        return $this->tags;
    }

    /**
     * Buscar etiquetas por nombre
     * 
     * @param string $search Término de búsqueda
     * @return array Etiquetas coincidentes
     */
    public function searchByName(string $search): array
    {
        $results = [];
        $search = strtolower($search);

        foreach ($this->tags as $tag => $info) {
            if (str_contains(strtolower($info['name'] ?? ''), $search) ||
                str_contains(strtolower($info['description'] ?? ''), $search)) {
                $results[$tag] = $info;
            }
        }

        return $results;
    }

    /**
     * Verificar si una etiqueta es un template
     * 
     * @param string $tag Etiqueta a verificar
     * @return bool
     */
    public function isTemplate(string $tag): bool
    {
        $info = $this->getTag($tag);
        return ($info['format'] ?? '') === 'Template';
    }
}
