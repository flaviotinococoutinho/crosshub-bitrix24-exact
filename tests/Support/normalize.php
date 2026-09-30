<?php

function normalizeCapture($value)
{
    if (is_array($value)) {
        if (isset($value['headers']) && in_array('Content-Type: application/json', $value['headers'], true) && isset($value['body']) && is_string($value['body'])) {
            $decoded = json_decode($value['body'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Payload JSON invalido');
            }
            $value['body'] = $decoded;
        }
        return array_map('normalizeCapture', $value);
    }
    if (!is_string($value)) {
        return $value;
    }
    // Remove apenas o relogio variavel usado no nome de eventos e na venda.
    $value = preg_replace('/[0-9]{6}(\+| )[0-9]{2}(?:%3A|:)[0-9]{2}(?:%3A|:)[0-9]{2}/', '<event-clock>', $value);
    return preg_replace('/"DtVenda": "[^"]+"/', '"DtVenda": "<sale-clock>"', $value);
}
