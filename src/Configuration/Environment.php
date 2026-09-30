<?php

namespace CrossHub\Configuration;

/** Leitura de .env sem eval, interpolacao, shell ou alteracao do ambiente global. */
final class Environment
{
    private $values;

    public function __construct($path)
    {
        $this->values = array();
        if (!is_file($path)) {
            return;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException('Nao foi possivel ler o arquivo de ambiente.');
        }
        foreach ($lines as $number => $line) {
            $this->parseLine($line, $number + 1);
        }
    }

    public function value($name, $default = null)
    {
        $external = getenv($name);
        if ($external !== false) {
            return $external;
        }
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
        return $default;
    }

    public function required($name)
    {
        $value = $this->value($name);
        if ($value === null || trim($value) === '') {
            throw new \RuntimeException('Configuracao obrigatoria ausente: ' . $name);
        }
        return $value;
    }

    public function boolean($name, $default)
    {
        $value = strtolower(trim((string) $this->value($name, $default ? 'true' : 'false')));
        if (in_array($value, array('true', '1', 'yes', 'on'), true)) {
            return true;
        }
        if (in_array($value, array('false', '0', 'no', 'off'), true)) {
            return false;
        }
        throw new \RuntimeException('Configuracao booleana invalida: ' . $name);
    }

    public function integer($name, $default, $minimum)
    {
        $value = (string) $this->value($name, $default);
        if (!preg_match('/^[0-9]+$/D', $value) || (float) $value > PHP_INT_MAX || (int) $value < $minimum) {
            throw new \RuntimeException('Configuracao inteira invalida: ' . $name);
        }
        return (int) $value;
    }

    private function parseLine($line, $number)
    {
        if ($number === 1 && substr($line, 0, 3) === "\xEF\xBB\xBF") {
            $line = substr($line, 3);
        }
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            return;
        }
        if (!preg_match('/^(?:export\s+)?([A-Z_][A-Z0-9_]*)\s*=\s*(.*)$/D', $line, $parts)) {
            throw new \RuntimeException('Formato .env invalido na linha ' . $number);
        }
        $this->values[$parts[1]] = $this->decode($parts[2], $number);
    }

    private function decode($value, $number)
    {
        if ($value === '' || ($value[0] !== "'" && $value[0] !== '"')) {
            return trim(preg_replace('/\s+#.*$/', '', $value));
        }
        if (!preg_match('/^([\'\"])(.*?)\1\s*(?:#.*)?$/D', $value, $parts)) {
            throw new \RuntimeException('Aspas .env invalidas na linha ' . $number);
        }
        return $parts[2];
    }
}
