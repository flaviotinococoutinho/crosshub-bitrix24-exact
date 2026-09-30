<?php

namespace CrossHub\Application;

use CrossHub\Integration\Bitrix;
use CrossHub\Ports\Logger;

final class ExactWebhook
{
    private $bitrix;
    private $logger;

    public function __construct(Bitrix $bitrix, Logger $logger)
    {
        $this->bitrix = $bitrix;
        $this->logger = $logger;
    }

    private function customField($id, $array) {
        foreach ($array as $key => $val) {
            if ($val['id'] === $id) {
                return $val['value'];
            }
        }
        return null;
    }

    private function serializeAnswers($questoes_arr=array()) {
        $resps = "";
        foreach ($questoes_arr as $etapas) {
            foreach($etapas["PerguntasRespostas"] as $perguntas){
                $resps .= $perguntas["Pergunta"]."\n \n";
                foreach($perguntas["Respostas"] as $resposta){
                    $resps .= $resposta["Resposta"].".\n";
                }
            }
        }
        return $resps;
    }

    public function handle(array $query, $input)
    {

        $resp_create_contact = "";
        $resp_create_deal = "";
        $resp_update_deal = "";
        $data = json_decode($input, 1);
        $tipo_lead = $query["tipo"];
        $tipo_evento = $data["Event"];

        $this->logger->write('exactincoming',
            ("\n VEIO ISSO \n ") .
            (json_encode($data)));
        $id_lead_exact = !empty($data["Lead"]["id"]) ? $data["Lead"]["id"] : "";

        $contato_email = !empty($data["Lead"]["Contatos"][0]["Email"]) ? $data["Lead"]["Contatos"][0]["Email"] : "";

        $contato_nome = !empty($data["Lead"]["Contatos"][0]["Nome"]) ? $data["Lead"]["Contatos"][0]["Nome"] : "";

        $contato_tel1 = !empty($data["Lead"]["Contatos"][0]["Tel1"]) ? $data["Lead"]["Contatos"][0]["Tel1"] : "";

        $contato_tel2 = !empty($data["Lead"]["Contatos"][0]["Tel2"]) ? $data["Lead"]["Contatos"][0]["Tel2"] : "";

        $nome_empresa = !empty($data["Lead"]["Empresa"]) ? $data["Lead"]["Empresa"] : "";

        $link_publico = !empty($data["Lead"]["LinkPublico"]) ? $data["Lead"]["LinkPublico"] : "";

        $link_feedback = !empty($data["Lead"]["LinkFeedBack"]) ? $data["Lead"]["LinkFeedBack"] : "";

        $prevendedor_nome = "";
        if(!empty($data["Lead"]["PreVendedor"]["Nome"])){
            $prevendedor_nome = $data["Lead"]["PreVendedor"]["Nome"];
            if(!empty($data["Lead"]["PreVendedor"]["UltimoNome"])){
                $prevendedor_nome .= " ".$data["Lead"]["PreVendedor"]["UltimoNome"];
            }
        }

        $vendedor_nome = !empty($data["Lead"]["Vendedor"]["Nome"]) ? $data["Lead"]["Vendedor"]["Nome"] : "";

        $vendedor_UltimoNome = !empty($data["Lead"]["Vendedor"]["UltimoNome"]) ? $data["Lead"]["Vendedor"]["UltimoNome"] : "";

        $vendedor_email = !empty($data["Lead"]["Vendedor"]["Email"]) ? $data["Lead"]["Vendedor"]["Email"] : "";

        $obs = !empty($data["Lead"]["Obs"]) ? $data["Lead"]["Obs"] : "";

        $data_inicio_agendamento = !empty($data["Agendamento"]["DtInicio"]) ? $data["Agendamento"]["DtInicio"] : "";

        $referencia = !empty($data["Agendamento"]["Referencia"]) ? $data["Agendamento"]["Referencia"] : "";
        switch ($tipo_lead) {
            case "captacoes":{
                $codigo_imovel = $this->customField("_codigoanuncio",$data["Lead"]["CamposPersonalizados"]);
                $id_lead_btx = $this->customField("_idbitrix",$data["Lead"]["CamposPersonalizados"]);
                $msg_cliente = $this->customField("_msgcliente",$data["Lead"]["CamposPersonalizados"]);
                $dataorigem = $this->customField("_datadeorigem",$data["Lead"]["CamposPersonalizados"]);
                break;}
            case "vendas":{
                $codigo_imovel = $this->customField("_codigoanuncio",$data["Lead"]["CamposPersonalizados"]);
                $id_lead_btx = $this->customField("_idbitrix",$data["Lead"]["CamposPersonalizados"]);
                $msg_cliente = $this->customField("_msgcliente",$data["Lead"]["CamposPersonalizados"]);
                $dataorigem = $this->customField("_datadeorigem",$data["Lead"]["CamposPersonalizados"]);
                break;}
            case "locacao":{
                $codigo_imovel = $this->customField("_codigoanuncio",$data["Lead"]["CamposPersonalizados"]);
                $id_lead_btx = $this->customField("_idbitrix",$data["Lead"]["CamposPersonalizados"]);
                $msg_cliente = $this->customField("_msgcliente",$data["Lead"]["CamposPersonalizados"]);
                $dataorigem = $this->customField("_datadeorigem",$data["Lead"]["CamposPersonalizados"]);
                break;}
            case "aluguel_especial":{
                $codigo_imovel = $this->customField("_codigodoimovel",$data["Lead"]["CamposPersonalizados"]);
                $id_lead_btx = $this->customField("_idbitrixespecial",$data["Lead"]["CamposPersonalizados"]);
                $msg_cliente = $this->customField("_mensagem",$data["Lead"]["CamposPersonalizados"]);
                $dataorigem = $this->customField("_datadeorigem",$data["Lead"]["CamposPersonalizados"]);
                break;}
        }
        $perguntas_resps = $this->serializeAnswers($data["Lead"]["Etapas"]);
        try {
            $msg = $this->bitrix->updateLead($id_lead_btx,$nome_empresa,$contato_nome,$contato_email,$contato_tel1,$contato_tel2,$link_publico,$codigo_imovel,$prevendedor_nome,$vendedor_nome,$vendedor_email,$obs,$perguntas_resps,$data_inicio_agendamento,$data_inicio_agendamento,$link_feedback,$referencia,$tipo_lead,$id_lead_exact,$tipo_evento);
        } catch (\Exception $e) {
            $msg = $e;
        }
        try {
            $id_contact_btx = "";
            $resp_update_contact = "";
            $resp_search = $this->bitrix->findContact(["UF_CRM_5C90EE1DD35B5"=>$id_lead_exact]);
            if(!empty($resp_search['ID'])){
                $id_contact_btx = $resp_search['ID'];
                $resp_update_contact = $resp_search['ID'];
            } else {
                if($tipo_evento == "event.schedule" || $tipo_evento == "event.leadqualified" || $tipo_evento == "event.leadqualified" || $tipo_evento == "event.leadwon"){
                    $resp_create_contact = $this->bitrix->createContact($contato_nome,"cpf",$contato_tel1,$contato_tel2,$contato_email,$data_inicio_agendamento,$link_publico,$link_feedback,$id_lead_exact,$codigo_imovel,$perguntas_resps,$prevendedor_nome,$tipo_lead,$vendedor_nome);
                    $id_contact_btx = $resp_create_contact;
                }

            }
        } catch (\Exception $e) {
            $msg = $e;
        }

        try {
            $resp_search_deal = $this->bitrix->findDeal(["UF_CRM_5C90EE1E02085"=>$id_lead_exact]);
            if(!empty($resp_search_deal['ID'])){
                $resp_update_deal = $this->bitrix->updateDeal($resp_search_deal['ID'],$tipo_evento,$vendedor_nome,$data_inicio_agendamento);
            } else {
                if($tipo_evento == "event.schedule" || $tipo_evento == "event.leadqualified" || $tipo_evento == "event.leadqualified" || $tipo_evento == "event.leadwon"){
                    $resp_create_deal = $this->bitrix->createDeal($contato_nome,$id_contact_btx,$id_lead_exact,$tipo_evento,$tipo_lead,$vendedor_nome,$prevendedor_nome,$data_inicio_agendamento,$codigo_imovel,$link_feedback,$link_publico,$perguntas_resps);
                }
            }
        } catch (\Exception $e) {
            $msg = $e;
        }

        $tipo_lead = mb_convert_case($tipo_lead, MB_CASE_UPPER, "UTF-8");

        $btx_sucess_boo = true;

        if( $msg == "ID is not defined or invalid."){
            $btx_sucess_boo = false;
        }

        try{
            if(is_bool($btx_sucess_boo)){
                $btx_sucess_boo = $btx_sucess_boo?"sucesso":"erro";
            }
            $msg_l = $this->bitrix->recordIntegrationEvent($contato_tel1,$contato_email, $btx_sucess_boo,"EXACT ".$tipo_lead ." TO BTX", $msg);
        } catch (\Exception $e) {
            $msg_l = $e;
        }

        $this->logger->write('exactincoming',
            ("\n Data: ".date('d').date('m').date('y')." ".date('H:i:s')) .
            ("\n Response do update lead do bitrix24 ".$msg) .
            ("\n Resposta UPDATE Update Btx ".$resp_update_contact) .
            ("\n Resposta CREATE Contact Btx ".$resp_create_contact) .
            ("\n Resposta SEARCHED Contact Btx ".json_encode($resp_search)) .
            ("\n Resposta SEARCHED Deal Btx ".json_encode($resp_search_deal)) .
            ("\n Resposta CREATE Deal Btx ".$resp_create_deal) .
            ("\n Resposta UPDATE Deal Btx ".$resp_update_deal) .
            ("\n Id do Lead Btx ".$id_lead_btx) .
            ("\n Nome da Empresa ".$nome_empresa) .
            ("\n Código do Imóvel ".$codigo_imovel) .
            ("\n Tipo do Evento ".$tipo_evento) .
            ("\n Nome do Pré-Vendedor ".$prevendedor_nome) .
            ("\n Tipo do Lead ".$tipo_lead) .
            ("\n Nome do Vendedor ".$vendedor_nome." ".$vendedor_UltimoNome) .
            ("\n E-mail do Vendedor ".$vendedor_email) .
            ("\n Obs ".$obs) .
            ("\n Data e Hora do Início do Agendamento ".$data_inicio_agendamento) .
            ("\n -------X-FEED EXACT-X------- \n ") .
            (json_encode($data)) .
            ("\n \n -------X------X------- \n\n "));
    }
}
