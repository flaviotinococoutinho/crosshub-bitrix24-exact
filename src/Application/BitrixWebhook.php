<?php

namespace CrossHub\Application;

use CrossHub\Integration\Bitrix;
use CrossHub\Integration\Exact;
use CrossHub\Ports\Logger;

final class BitrixWebhook
{
    private $bitrix;
    private $exact;
    private $logger;
    private $mappings;

    public function __construct(Bitrix $bitrix, Exact $exact, Logger $logger, array $mappings)
    {
        $this->bitrix = $bitrix;
        $this->exact = $exact;
        $this->logger = $logger;
        $this->mappings = $mappings;
    }

    public function handle(array $query)
    {
        $msg = "";
        $email = \CrossHub\Domain\Normalizer::email($query['email']);
        $nome = \CrossHub\Domain\Normalizer::fullName($query['nome'],$query['sobrenome']);
        $nome_empresa = !empty($query['nome_empresa']) ? $query['nome_empresa'] : $nome;
        $tel1 = !empty($query['tel_work']) ? $query['tel_work'] : $query['tel_celular'];
        $tel2 = $query['tel_outro'];
        $cod_imovel = $query['cod_imovel'];
        $btx_id_lead = $query['id'];
        $meio_fav_contato = \CrossHub\Domain\Normalizer::meaning($query['como_chamado'], $this->mappings['enum_btx_meio_fav_contato'], $query['como_chamado']);
        $regiao_procura_imovel = \CrossHub\Domain\Normalizer::meaning($query['regiao_procura'], $this->mappings['enum_btx_regiao_procura_imovel'], $query['regiao_procura']);
        $dormitorios = \CrossHub\Domain\Normalizer::meaning($query['dormitorios'], $this->mappings['enum_btx_dormitorios'], $query['dormitorios']);
        $possui_mobilias = \CrossHub\Domain\Normalizer::meaning($query['possui_mobilia'], $this->mappings['enum_btx_possui_mobilias'], $query['possui_mobilia']);
        $faixa_valor_aluguel = \CrossHub\Domain\Normalizer::meaning($query['estima_invest_aluguel'], $this->mappings['enum_btx_faixa_valor_aluguel'], $query['estima_invest_aluguel']);
        $previsao_mudanca = \CrossHub\Domain\Normalizer::meaning($query['previsao_mudanca'], $this->mappings['enum_btx_previsao_mudanca'], $query['previsao_mudanca']);
        $possui_fiador = \CrossHub\Domain\Normalizer::meaning($query['possui_fiador'], $this->mappings['enum_btx_possui_fiador'], $query['possui_fiador']);
        $tipo_lead = \CrossHub\Domain\Normalizer::meaning($query['tipo_lead'], $this->mappings['enum_btx_tipo_lead'], $query['tipo_lead']);
        $comment_btx = !empty($query['comentariobtx']) ? $query['comentariobtx'] : "";
        $tipo_origem = $query['tipo_origem'];
        $observacoes = "";
        $observacoes .= !empty($comment_btx) ? "Comentário: ".$comment_btx." \n" : "";
        $observacoes .= !empty($meio_fav_contato) ? "Meio de contato: ".$meio_fav_contato." \n" : "";
        $observacoes .= !empty($regiao_procura_imovel) ? "Região de procura do imóvel: ".$regiao_procura_imovel." \n" : "";
        $observacoes .= !empty($dormitorios) ? "Dormitórios: ".$dormitorios." \n" : "";
        $observacoes .= !empty($possui_mobilias) ? "Possui mobílias? ".$possui_mobilias." \n" : "";
        $observacoes .= !empty($faixa_valor_aluguel) ? "Faixa do valor disposto para o aluguel: ".$faixa_valor_aluguel." \n" : "";
        $observacoes .= !empty($previsao_mudanca) ? "Possível previsão de mudança: ".$previsao_mudanca." \n" : "";
        $observacoes .= !empty($possui_fiador) ? "Possui fiador? ".$possui_fiador." \n" : "";
        $response_exact = $this->exact->createLead($nome_empresa,$email,$nome,$tel1,$tel2,$cod_imovel,$btx_id_lead, $observacoes, $tipo_lead,$tipo_origem,$this->mappings['array_tipo_origembtx']);
        $response_exact = json_decode($response_exact,1);
        $id_exact_lead = !empty($response_exact["id"]) ? $response_exact["id"] : null; //ID que temos no retorno, essa variável persiste com o val se chave IDS for inexistente.
        $id_exact_lead = !empty($response_exact['ids']) ? $response_exact['ids'][0] : $id_exact_lead; //Geralmente se já existente(erro), a chave vem no plural, senão, pega val anterior.
        $detail_item_exact = null;
        $etapa_exact = "";
        if(!empty($id_exact_lead) && !empty($tipo_lead)){
            $detail_item_exact = $this->exact->findLead($id_exact_lead,$tipo_lead);
            $detail_item_exact = json_decode($detail_item_exact,1);
            if(array_key_exists("Etapa",$detail_item_exact)){
                $etapa_exact = $detail_item_exact["Etapa"];
            } else {
                $etapa_exact = "";
            }
        }
        $exact_sucess_boo = false;
        $exact_sucess_boo = !empty($response_exact['success']) ? $response_exact['success'] : $exact_sucess_boo;
        if ($exact_sucess_boo){
            $msg .= "\n Status Adicionar Lead Exact: ";
            $msg .= json_encode($response_exact);
        } else {
            $msg .= "\n Status Adicionar Lead Exact: OPS!";
            $msg .= json_encode($response_exact);
        }

        if ($etapa_exact == "Descartados" && !$exact_sucess_boo){

            $msg .= "\n Status Obter dados do Lead Exact: ";
            $msg .= $this->exact->recoverLead($id_exact_lead,$tipo_lead);
            $msg .= "\n Status Atualizar dados do Lead Exact: ";
            $msg .= $this->exact->updateLead($id_exact_lead,$nome_empresa,$cod_imovel,$btx_id_lead,$observacoes,$tipo_lead,$tipo_origem,$tel1,$tel2,$this->mappings['array_tipo_origembtx']);
        }

        if (($etapa_exact == "Entrada" || $etapa_exact == "Filtro 1" || $etapa_exact == "Filtro 2" || $etapa_exact == "Qualificados") && !$exact_sucess_boo){
            $msg .= "Status ID Btx Lead Descartado: ";
            $msg .= $this->bitrix->discardLead($btx_id_lead,$id_exact_lead);
            $msg .= "\n";
        }

        try{
            if(is_bool($exact_sucess_boo)){
                $exact_sucess_boo = $exact_sucess_boo?"sucesso":"erro";
            }
            $msg .= $this->bitrix->recordIntegrationEvent($tel1,$email, $exact_sucess_boo,"BTXLEAD OK TO EXACT", $msg);
        } catch (\Exception $e) {
            $msg .= $e;
        }

        $this->logger->write('btxincoming',
            ("\n\n Data: ".date('d').date('m').date('y')." ".date('H:i:s')) .
            ("\n ID do bitrix24: ".$btx_id_lead) .
            ("\n Email bitrix24: ".$email) .
            ("\n Nome bitrix24: ".$nome) .
            ("\n Telefone home btx: ".$tel1) .
            ("\n Telefone work btx: ".$tel2) .
            ("\n Nome_empresa btx: ".$nome_empresa) .
            ("\n Tipo Lead: ".$tipo_lead) .
            ("\n Tipo Lead Raw: ".$query['tipo_lead']) .
            ("\n Tipo Origem: ".$tipo_origem) .
            ("\n Cod Imovel: ".$cod_imovel) .
            ("\n Sucesso no add do exact? ".$exact_sucess_boo) .
            ("\n Id do lead no exact: ".$id_exact_lead) .
            ("\n Etapa exact: ".$etapa_exact) .
            ("\n msg script: ".$msg) .
            ("\n Obs bitrix24: ".$observacoes) .
            ("\n Response ADD do exact: ".json_encode($response_exact)));
    }
}
