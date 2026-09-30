<?php

namespace CrossHub\Integration;

use CrossHub\Ports\HttpClient;
use CrossHub\Ports\Logger;
use CrossHub\Http\Request;

/** Adaptador do contrato Bitrix; preserva campos, payloads e retornos existentes. */
final class Bitrix
{
    private $http;
    private $logger;
    private $settings;

    public function __construct(HttpClient $http, Logger $logger, array $settings)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->settings = $settings;
    }

    private function mapping($name)
    {
        return $this->settings['bitrix_mappings'][$name];
    }

    private function request($method, $url, $body, array $headers, $verifyPeer)
    {
        return $this->http->send(new Request($method, $url, $body, $headers, $verifyPeer));
    }

    public function findDeal($filter_arr, $queryUrl = null) {
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;
        $saida = null;
        $fields_array = array();
        $fields_array['filter'] = $filter_arr;
        $fields_array['select'] = ["ID","TITLE","STAGE_ID","CONTACT_ID","UF_*"];
        $queryUrl  = $queryUrl."/crm.deal.list";

        $queryData = http_build_query($fields_array);

        $saida = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);
        $saida  = json_decode($saida , 1);
        if(!empty($saida['result'][0])){
            return $saida['result'][0];
        }
        if(!empty($saida['error_description'])){
            return $saida['error_description'];
        }
        return $saida['result'];
    }

    public function findContact($filter_arr, $queryUrl = null) {
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;
        $saida = null;
        $fields_array = array();
        $fields_array['order'] = ["DATE_CREATE"=>"ASC"];
        $fields_array['filter'] = $filter_arr;
        $fields_array['select'] = ["ID","NAME","LAST_NAME","SOURCE_ID","STAGE_ID","TYPE_ID","CONTACT_ID","UF_*"];
        $queryUrl  = $queryUrl."/crm.contact.list";

        $queryData = http_build_query($fields_array);

        $saida = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);
        $saida  = json_decode($saida , 1);
        if(!empty($saida['result'][0])){
            return $saida['result'][0];
        }
        if(!empty($saida['error_description'])){
            return $saida['error_description'];
        }
        return $saida['result'];
    }

    public function createContact ($nome="",$cpf="",$tel1="",$tel2="",$email="",$data_agendamento="",$link_pub="",$link_feedback_vendor="",$id_exact="",$codimovel="",$comentarios_serializado="",$pre_vendor="",$tipo_lead="",$vendor="",$queryUrl = null) {
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;

        $nome_array = explode(' ', $nome, 2);
        $tipo_lead_id_arr = $this->mapping('createContact.tipo_lead_id_arr');
        $tipo_lead = $tipo_lead_id_arr[$tipo_lead];

        $enum_vendor_arr = $this->mapping('createContact.enum_vendor_arr');
        $vendor = trim($vendor); //Evita espaços no cadastro de vendedor
        $tipo_agencia_id = array_key_exists($vendor,$enum_vendor_arr)?$enum_vendor_arr[$vendor]:"7197";
        $tel = empty($tel1) && empty($tel2) ? "" : array(); //no btx string vazia significa null e elimina dados antigos, reatualizando campos.
        if(!empty($tel1)){
            $tel[] = array("VALUE" => $tel1, "VALUE_TYPE" => "HOME" );
        }
        if(!empty($tel2)){
            $tel[] = array("VALUE" => $tel2, "VALUE_TYPE" => "WORK" );
        }

        $fieldsContact = array();
        $fieldsContact['TITLE'] = $nome;
        $fieldsContact['NAME'] = $nome_array[0];
        $fieldsContact['LAST_NAME'] = !empty($nome_array[1])?$nome_array[1]:""; //Evita offset na matriz
        $fieldsContact['PHONE'] = $tel;
        $fieldsContact['EMAIL'] = array(array("VALUE" => strtolower($email), "VALUE_TYPE" => "HOME" ));
        $fieldsContact['OPENED'] = "Y";
        $fieldsContact['EXPORT'] = "Y";
        $fieldsContact['TYPE_ID'] = "35";//35 ID do Tipo cliente
        $fieldsContact['CURRENCY_ID'] = "BRL"; //tipo string para definir origem
        $fieldsContact['COMMENTS'] = $comentarios_serializado; //reposta da requisição que possa gerar o erro
        $fieldsContact['UF_CRM_1554913746'] = $cpf; //CPF ou CNPJ
        $fieldsContact['UF_CRM_5C77DFD990792'] = $tipo_lead; //Tipo do Lead
        $fieldsContact['UF_CRM_5C90EE1DD35B5'] = $id_exact; //ID Exact do Lead
        $fieldsContact['UF_CRM_5C77DFD74733C'] = $codimovel; //Código do Imóvel
        $fieldsContact['UF_CRM_5C77DFD6E4F62'] = $tipo_agencia_id; //Enum com agencia
        $fieldsContact['UF_CRM_5C77DFD99B758'] = $pre_vendor; //Pré-Vendedor
        $fieldsContact['UF_CRM_5C77DFD9A236E'] = $link_feedback_vendor; //Link do Spotter
        $fieldsContact['UF_CRM_5C8C3B71AB533'] = $link_pub; //Link Público Exact
        $fieldsContact['UF_CRM_5C77DFD9A9603'] = $data_agendamento; //Data do Agendamento
        $this->logger->write('contact-create',
            ("\n\n Data: ".date('d').date('m').date('y')." ".date('H:i:s')) .
            ("\n Nome: ".$nome) .
            ("\n Tipo do lead: ".$tipo_lead) .
            ("\n Link feedback vendor: ".$link_feedback_vendor) .
            ("\n Link Público Exact: ".$link_pub) .
            ("\n Pré-vendedor: ".$pre_vendor) .
            ("\n Data do agendamento: ".$data_agendamento));

        $cadastro_array = array();
        $cadastro_array['fields'] = $fieldsContact;
        $cadastro_array['params'] = array("REGISTER_SONET_EVENT" => "Y");

        $queryData = http_build_query($cadastro_array);

        $queryUrl  .= "/crm.contact.add.json";
        $result = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);

        $result = json_decode($result, 1);
        if(!empty($result['result'])){
            return $result['result'];
        }
        return $result['error_description'];
    }

    public function updateLead($id_btx = "", $empresa = "", $nome = "", $email="", $tel1="",$tel2="", $link_pub="",$cod_imovel="", $pre_vendor="",$vendor="",$email_vendor="",$comentario="",$descricao="",$data_agendamento="",$hora_agendamento="",$link_feedback_vendor="",$referencia="", $tipo_lead="",$exact_lead_id=null, $trigger_agendamento="", $queryUrl = null){
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;
        $tipo_lead_id_arr = $this->mapping('updateLead.tipo_lead_id_arr');
        $tipo_lead = $tipo_lead_id_arr[$tipo_lead];
        $enum_status_agendamento = $this->mapping('updateLead.enum_status_agendamento');
        $status_id = $enum_status_agendamento[$trigger_agendamento];
        $tel = empty($tel1) && empty($tel2) ? "" : array(); //no btx string vazia significa null e elimina dados antigos, reatualizando campos.
        if(!empty($tel1)){
            $tel[] = array("VALUE" => $tel1, "VALUE_TYPE" => "HOME" );
        }
        if(!empty($tel2)){
            $tel[] = array("VALUE" => $tel2, "VALUE_TYPE" => "WORK" );
        }

        $enum_vendor_arr = $this->mapping('updateLead.enum_vendor_arr');
        $vendor = trim($vendor); //Evita espaços no cadastro de vendedor
        $vendor_id = array_key_exists($vendor,$enum_vendor_arr)?$enum_vendor_arr[$vendor]:"2963";

        $queryUrl  .= "/crm.lead.update.json";
        $queryData = http_build_query(array(
                'id' => $id_btx,
                'fields' => array(
                    "NAME" => $nome,
                    "COMPANY_TITLE" =>$empresa,
                    "STATUS_ID" => $status_id,
                    "OPENED" => "Y",
                    "UF_CRM_1551125599" => $link_feedback_vendor,
                    "UF_CRM_1552689459" => $link_pub,
                    "UF_CRM_1553011410" => $email_vendor,
                    "UF_CRM_1549555198" => $vendor_id,
                    "UF_CRM_1553011368" => $vendor,
                    "UF_CRM_1551125244" => $pre_vendor,
                    "UF_CRM_1552961611" => $exact_lead_id,
                    "COMMENTS"	=> $descricao,
                    "PHONE" => $tel,
                    "UF_CRM_1551125807"=> $data_agendamento,
                    "UF_CRM_1551375246"	=> 	$referencia,
                    "EMAIL" => array(array("VALUE" => strtolower($email), "VALUE_TYPE" => "HOME" )),
                ),
                'params' => array("REGISTER_SONET_EVENT" => "Y")
            ));

        $result = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);

        $result = json_decode($result, 1);
        if(!empty($result['result'])){
            return $id_btx;
        }
        return $result['error_description'];

    }

    public function discardLead($id_btx = "",$exact_lead_id=null, $queryUrl = null){
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;

        $queryUrl  .= "/crm.lead.update.json";
        $queryData = http_build_query(array(
                'id' => $id_btx,
                'fields' => array(
                    "STATUS_ID" => "5",
                    "OPENED" => "Y",
                    "UF_CRM_1552961611" => $exact_lead_id,
                ),
                'params' => array("REGISTER_SONET_EVENT" => "Y")
            ));

        $result = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);

        $result = json_decode($result, 1);
        if(!empty($result['result'])){
            return $id_btx;
        }
        return $result['error_description'];

    }

    public function createDeal($nome="",$contact_id_btx="",$id_lead_exact="",$status="",$tipo_lead="",$vendor="",$pre_vendor="",$data_agendamento="",$cod_imovel="",$link_feedback_vendor="",$link_pub="",$descricao="",$queryUrl = null) {
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;
        $vendedor = trim(mb_strtolower($vendor, 'UTF-8'));
        $conversaoaccents = array('á' => 'a','à' => 'a','ã' => 'a','â' => 'a', 'é' => 'e',
            'ê' => 'e', 'í' => 'i', 'ï'=>'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', "ö"=>"o",
            'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ'=>'n','-' =>' ','/'=>' ','|'=>' ','&'=>' ');
        $vendedor = strtr($vendedor, $conversaoaccents);
        $vendedor = mb_ereg_replace('\s+', ' ',$vendedor);
        $stage_id = "";
        $category_status = $this->mapping('createDeal.category_status');

        if(array_key_exists($vendedor,$category_status)) {
            if(!empty($status) && array_key_exists($status, isset($category_status[$vendedor]['status']) ? $category_status[$vendedor]['status'] : array()) ){
                $stage_id = $category_status[$vendedor]['status'][$status];
            } else {
                $stage_id = $category_status[$vendedor]['status']['event.schedule'];
            }
            $category_id = $category_status[$vendedor]['category'];
        } else {
            $stages_main = $this->mapping('createDeal.stages_main');
            if(!empty($status) && array_key_exists($status, isset($category_status[$vendedor]['status']) ? $category_status[$vendedor]['status'] : array())){
                $stage_id = $stages_main[$status];
            } else {
                $stage_id = $stages_main['event.schedule'];
            }
            $category_id = "129";
        }
        $tipo_lead_id_arr = $this->mapping('createDeal.tipo_lead_id_arr');
        $tipo_lead = $tipo_lead_id_arr[$tipo_lead];

        $enum_vendor_arr = $this->mapping('createDeal.enum_vendor_arr');
        $vendor = trim($vendor); //Evita espaços no cadastro de vendedor
        $tipo_agencia_id = array_key_exists($vendor,$enum_vendor_arr)?$enum_vendor_arr[$vendor]:"7185";
        $this->logger->write('deal-create',
            ("\n\n Data: ".date('d').date('m').date('y')." ".date('H:i:s')) .
            ("\n Nome: ".$nome) .
            ("\n Vendor: ".$vendor) .
            ("\n Pré-vendor: ".$pre_vendor) .
            ("\n Trigger: ".$status) .
            ("\n Stage: ".$stage_id) .
            ("\n Category: ".$category_id) .
            ("\n Cod Imovel: ".$cod_imovel) .
            ("\n Link Publico: ".$link_pub) .
            ("\n Link Feedback: ".$link_feedback_vendor) .
            ("\n Data Agendamento: ".$data_agendamento));

        $fieldsContact = array();
        $fieldsContact['TITLE'] = mb_ereg_replace('\s+', ' ',$nome);
        $fieldsContact['OPENED'] = "Y";
        $fieldsContact['EXPORT'] = "Y";
        $fieldsContact['CATEGORY_ID'] = $category_id;
        $fieldsContact['STAGE_ID'] = $stage_id;
        $fieldsContact['COMMENTS'] = $descricao;
        $fieldsContact['CONTACT_IDS'] = array($contact_id_btx);
        $fieldsContact['UF_CRM_5C6D511934A76'] = $tipo_agencia_id; //Enum com agencia
        $fieldsContact['UF_CRM_5C7004A10252B'] = $tipo_lead; //Tipo do lead
        $fieldsContact['UF_CRM_5C90EE1E02085'] = $id_lead_exact; //Id do lead no exact
        $fieldsContact['UF_CRM_5C77DFD9C66B6'] = $data_agendamento; //Data do agendamento
        $fieldsContact['UF_CRM_5C782B23B6106'] = $cod_imovel; //Código do imóvel vindo do exact
        $fieldsContact['UF_CRM_5C77DFD9BFF43'] = $link_feedback_vendor; //Link do Spotter
        $fieldsContact['UF_CRM_5C8C3B71D8BC4'] = $link_pub; //Link Público Exact
        $fieldsContact['UF_CRM_5C77DFD9B5629'] = $pre_vendor; //Pré-vendedor

        $cadastro_array = array();
        $cadastro_array['fields'] = $fieldsContact;
        $cadastro_array['params'] = array("REGISTER_SONET_EVENT" => "Y");

        $queryData = http_build_query($cadastro_array);
        $queryUrl  .= "/crm.deal.add.json";
        $result = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);

        $result = json_decode($result, 1);
        if(!empty($result['result'])){
            return $result['result'];
        }
        return $result['error_description'];
    }

    public function updateDeal($id_btx_deal = "", $trigger_agendamento="",$vendedor="",$data_agendamento="", $queryUrl = null){
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;
        $vendedor = trim(mb_strtolower($vendedor, 'UTF-8'));
        $conversaoaccents = array('á' => 'a','à' => 'a','ã' => 'a','â' => 'a', 'é' => 'e',
            'ê' => 'e', 'í' => 'i', 'ï'=>'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', "ö"=>"o",
            'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ'=>'n','-' =>'','/'=>' ','|'=>' ','&'=>' ');
        $vendedor = strtr($vendedor, $conversaoaccents);
        $vendedor = mb_ereg_replace('\s+', ' ',$vendedor);
        $stage_id = "";
        $category_status = $this->mapping('updateDeal.category_status');

        if(array_key_exists($vendedor,$category_status)) {
            $stage_id = $category_status[$vendedor][$trigger_agendamento];
        } else {
            $stages_main = $this->mapping('updateDeal.stages_main');
            $stage_id = $stages_main[$trigger_agendamento];
        }
        $this->logger->write('deal-update',
            ("\n\n Data: ".date('d').date('m').date('y')." ".date('H:i:s')) .
            ("\n ID do Negócio BTX: ".$id_btx_deal) .
            ("\n Vendor: ".$vendedor) .
            ("\n Trigger: ".$trigger_agendamento) .
            ("\n Stage: ".$stage_id) .
            ("\n Data Agendamento: ".$data_agendamento));

        if (!empty($stage_id)) {
            $queryUrl  .= "/crm.deal.update.json";
            $queryData = http_build_query(array(
                    'id' => $id_btx_deal,
                    'fields' => array(
                        "STAGE_ID" => $stage_id,
                        "UF_CRM_5C77DFD9C66B6" => $data_agendamento,
                    ),
                    'params' => array("REGISTER_SONET_EVENT" => "Y")
                ));
            $result = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);

            $result = json_decode($result, 1);
            if(!empty($result['result'])){
                return $result['result'];
            }

            if(!empty($result['error_description'])){
                return $result['error_description'];
            }
            return null;
        }
        return null;

    }
    public function recordIntegrationEvent($tel_customer = "",$email_customer = "", $status_evento="",$origem_evento="", $msg_evento="",$queryUrl = null){
        $queryUrl = $queryUrl === null ? $this->settings['bitrix_hook'] : $queryUrl;
        $enum_status_event = $this->mapping('recordIntegrationEvent.enum_status_event');
        $event_id = array_key_exists(strtolower(trim($status_evento)),$enum_status_event)?$enum_status_event[strtolower(trim($status_evento))]:"159";

        $origem_evento = !empty($origem_evento)?mb_convert_case(trim($origem_evento), MB_CASE_UPPER, "UTF-8"):"SCRIPT";
        $tel = !empty($tel_customer)?$tel_customer:"";

        $msg_evento = !empty($msg_evento)?$msg_evento:"VAZIO";
        $name_core = !empty($email_customer)?$email_customer:$tel;
        $name = $name_core." ".date('d').date('m').date('y')." ".date('H:i:s');

        if (!empty($event_id)) {
            $queryUrl  .= "/lists.element.add.json";

            $fieldsList = array();
            $fieldsList['NAME'] = $name;
            $fieldsList['PROPERTY_113'] = $event_id; //status de msg, Sucesso, erro, vazio, aviso...
            $fieldsList['PROPERTY_111'] = $origem_evento; //tipo string para definir origem
            $fieldsList['PROPERTY_115'] = $msg_evento; //reposta da requisição que possa gerar o erro

            $cadastro_list_array = array(
                "IBLOCK_TYPE_ID" => "lists",
                "IBLOCK_ID" => 33,
                "ELEMENT_CODE" => $name,
                "FIELDS" => $fieldsList
            );

            $queryData = http_build_query($cadastro_list_array);
            $result = $this->request('POST', $queryUrl, $queryData, array(), $this->settings['bitrix_verify_peer']);

            $result = json_decode($result, 1);
            if(!empty($result['result'])){
                return $result['result'];
            }

            if(!empty($result['error_description'])){
                return $result['error_description'];
            }
            return null;
        }
        return null;

    }
}
