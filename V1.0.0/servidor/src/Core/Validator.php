<?php

declare(strict_types=1);

namespace Skatenea\Core;

final class Validator
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    public static function validate(array $input, array $rules): array
    {
        $clean = [];
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $value = $input[$field] ?? null;
            $present = array_key_exists($field, $input) && $value !== '';
            $required = in_array('required', $ruleList, true);

            if (!$present) {
                if ($required) {
                    $errors[$field][] = 'Es obligatorio.';
                }
                continue;
            }

            $isNullable = in_array('nullable', $ruleList, true);
            if ($value === null && $isNullable) {
                $clean[$field] = null;
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'required' || $rule === 'nullable') {
                    continue;
                }

                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = 'Debe ser texto.';
                        }
                        break;
                    case 'int':
                        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $errors[$field][] = 'Debe ser un entero.';
                        } else {
                            $value = (int) $value;
                        }
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field][] = 'Debe ser numérico.';
                        } else {
                            $value = (float) $value;
                        }
                        break;
                    case 'boolean':
                        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($bool === null) {
                            $errors[$field][] = 'Debe ser booleano.';
                        } else {
                            $value = $bool;
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = 'Debe ser un correo válido.';
                        }
                        break;
                    case 'uuid':
                        if (!is_string($value) || !Uuid::isValid($value)) {
                            $errors[$field][] = 'Debe ser un UUID válido.';
                        }
                        break;
                    case 'date':
                        if (!is_string($value) || strtotime($value) === false) {
                            $errors[$field][] = 'Debe ser una fecha válida.';
                        }
                        break;
                    case 'min':
                        if (is_string($value) && mb_strlen($value) < (int) $parameter) {
                            $errors[$field][] = 'Debe tener al menos ' . (int) $parameter . ' caracteres.';
                        } elseif ((is_int($value) || is_float($value)) && (float) $value < (float) $parameter) {
                            $errors[$field][] = 'Debe ser mayor o igual a ' . $parameter . '.';
                        }
                        break;
                    case 'max':
                        if (is_string($value) && mb_strlen($value) > (int) $parameter) {
                            $errors[$field][] = 'No puede superar ' . (int) $parameter . ' caracteres.';
                        } elseif ((is_int($value) || is_float($value)) && (float) $value > (float) $parameter) {
                            $errors[$field][] = 'Debe ser menor o igual a ' . $parameter . '.';
                        }
                        break;
                    case 'in':
                        $allowed = $parameter !== null ? explode(',', $parameter) : [];
                        if (!in_array((string) $value, $allowed, true)) {
                            $errors[$field][] = 'Valor no permitido.';
                        }
                        break;
                    case 'regex':
                        if ($parameter === null || !is_string($value) || preg_match($parameter, $value) !== 1) {
                            $errors[$field][] = 'Formato inválido.';
                        }
                        break;
                    default:
                        $errors[$field][] = 'Regla de validación desconocida: ' . $name;
                }
            }

            if (!isset($errors[$field])) {
                $clean[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw HttpException::unprocessable('Revisa los datos enviados.', $errors);
        }

        return $clean;
    }

    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
