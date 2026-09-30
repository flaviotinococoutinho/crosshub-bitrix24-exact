<?php

namespace CrossHub\Integration;

use CrossHub\Http\Request;
use CrossHub\Ports\HttpClient;

/** Contrato Exact: conta escolhe token; operacao escolhe endpoint e campos. */
final class Exact
{
    private $http;
    private $settings;

    public function __construct(HttpClient $http, array $settings)
    {
        $this->http = $http;
        $this->settings = $settings;
    }

    public function createLead($empresa = '', $contato_email = '', $contato_nome = '', $contato_tel1 = '', $contato_tel2 = '', $cod_imovel = '', $id_btx_lead = '', $obs = '', $tipo_lead = 'Captacao Vendas', $tipo_origem = '', array $array_tipo_origembtx = array())
    {
        $payload = array(
            'Empresa' => (string) $empresa,
            'Contatos' => array(array('Email' => (string) $contato_email, 'Nome' => (string) $contato_nome, 'Tel1' => (string) $contato_tel1, 'Tel2' => (string) $contato_tel2)),
            'Obs' => (string) $obs,
        );
        $payload = array_merge($payload, $this->origin($tipo_lead, $tipo_origem, $array_tipo_origembtx));
        $payload['CamposPersonalizados'] = $this->customFields($tipo_lead, $id_btx_lead, $cod_imovel, 'add');
        return $this->send('POST', 'add', $tipo_lead, $payload);
    }

    public function updateLead($id_lead_exact = null, $empresa = '', $cod_imovel = '', $id_btx_lead = '', $obs = '', $tipo_lead = 'Captacao Vendas', $tipo_origem = '', $contato_tel1 = '', $contato_tel2 = '', array $array_tipo_origembtx = array())
    {
        $payload = array(
            'Id' => (string) $id_lead_exact,
            'Contatos' => array(array('Tel1' => (string) $contato_tel1, 'Tel2' => (string) $contato_tel2)),
            'Empresa' => (string) $empresa,
            'Obs' => (string) $obs,
        );
        $payload = array_merge($payload, $this->origin($tipo_lead, $tipo_origem, $array_tipo_origembtx));
        $payload['CamposPersonalizados'] = $this->customFields($tipo_lead, $id_btx_lead, $cod_imovel, 'update');
        return $this->send('PUT', 'update', $tipo_lead, $payload);
    }

    public function findLead($id_exact = null, $tipo_lead = 'Captacao Vendas')
    {
        return $this->http->send(new Request('GET', $this->settings['exact_urls']['get'] . $id_exact, null, $this->headers($tipo_lead)));
    }

    public function recoverLead($id_lead_exact = null, $tipo_lead = 'Captacao Vendas')
    {
        return $this->send('POST', 'recover', $tipo_lead, array('id' => (string) $id_lead_exact));
    }

    private function origin($type, $source, array $mappings)
    {
        $source = mb_strtoupper($source, 'UTF-8');
        $origin = isset($mappings[$type][$source]) ? $mappings[$type][$source] : array('origem' => '', 'suborigem' => '');
        $fields = array('Origem' => array('value' => (string) $origin['origem']));
        if ($type === 'Aluguel' || $type === 'Vendas') {
            $fields['SubOrigem'] = array('value' => (string) $origin['suborigem']);
        }
        return $fields;
    }

    private function customFields($type, $bitrixId, $propertyCode, $operation)
    {
        $bitrixField = $type === 'Aluguel_Especial' ? '_idbitrixespecial' : '_idbitrix';
        // O contrato historico Especial usa campos distintos ao criar e atualizar.
        $propertyField = $type === 'Aluguel_Especial' && $operation === 'add' ? '_codigodoimovel' : '_codigoanuncio';
        return array(
            array('id' => $bitrixField, 'value' => (string) $bitrixId),
            array('id' => $propertyField, 'value' => (string) $propertyCode),
        );
    }

    private function send($method, $operation, $type, array $payload)
    {
        $body = json_encode($payload);
        if ($body === false) {
            throw new \RuntimeException('Nao foi possivel serializar o lead para a Exact.');
        }
        return $this->http->send(new Request($method, $this->settings['exact_urls'][$operation], $body, $this->headers($type)));
    }

    private function headers($type)
    {
        if (!isset($this->settings['exact_tokens'][$type])) {
            throw new \InvalidArgumentException('Tipo de conta Exact desconhecido.');
        }
        return array('Content-Type: application/json', 'token_exact:' . $this->settings['exact_tokens'][$type]);
    }
}
