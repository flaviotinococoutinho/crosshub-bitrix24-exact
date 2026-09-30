<?php
$query = array(
    'email'=>'PERSON@example.invalid','nome'=>'Pessoa Exemplo','sobrenome'=>'Exemplo','nome_empresa'=>'Empresa',
    'tel_work'=>'111','tel_celular'=>'222','tel_outro'=>'333','cod_imovel'=>'code','id'=>'12',
    'como_chamado'=>'3659','regiao_procura'=>'779','dormitorios'=>'765','possui_mobilia'=>'773',
    'estima_invest_aluguel'=>'719','previsao_mudanca'=>'731','possui_fiador'=>'749','tipo_lead'=>'2265',
    'comentariobtx'=>'notes','tipo_origem'=>'OLX',
);
$cases = array(
    'bitrix-new'=>array('bitrix',$query,'',array('{"success":true,"id":123}','{"Etapa":"Entrada"}','{"result":99}')),
    'bitrix-recover'=>array('bitrix',$query,'',array('{"success":false,"ids":[123]}','{"Etapa":"Descartados"}','{"success":true}','{"success":true}','{"result":99}')),
    'bitrix-active'=>array('bitrix',$query,'',array('{"success":false,"ids":[123]}','{"Etapa":"Qualificados"}','{"result":true}','{"result":99}')),
    'bitrix-rejected'=>array('bitrix',$query,'',array('{"success":false}','{"result":99}')),
);
foreach (array('captacoes','vendas','locacao','aluguel_especial') as $type) {
    foreach (array('event.schedule','event.reschedule','event.leadwon','event.cancelschedule','event.leadlost') as $event) {
        $payload=array(
            'Event'=>$event,
            'Lead'=>array(
                'id'=>'123','Empresa'=>'Empresa','Contatos'=>array(array('Email'=>'person@example.invalid','Nome'=>'Pessoa Exemplo','Tel1'=>'111','Tel2'=>'222')),
                'LinkPublico'=>'public','LinkFeedBack'=>'feedback','PreVendedor'=>array('Nome'=>'Pre','UltimoNome'=>'Pessoa'),
                'Vendedor'=>array('Nome'=>'locacao alfa','UltimoNome'=>'','Email'=>'vendor@example.invalid'),
                'Obs'=>'notes','CamposPersonalizados'=>array(
                    array('id'=>'_idbitrix','value'=>'12'),array('id'=>'_idbitrixespecial','value'=>'12'),
                    array('id'=>'_codigoanuncio','value'=>'code'),array('id'=>'_codigodoimovel','value'=>'code'),
                ),
                'Etapas'=>array(array('PerguntasRespostas'=>array(array('Pergunta'=>'Pergunta','Respostas'=>array(array('Resposta'=>'Resposta')))))),
            ),
            'Agendamento'=>array('DtInicio'=>'2020-01-02','Referencia'=>'ref'),
        );
        $cases['exact-existing-'.$type.'-'.$event]=array('exact',array('tipo'=>$type),json_encode($payload),array('{"result":true}','{"result":[{"ID":"21"}]}','{"result":[{"ID":"42"}]}','{"result":true}','{"result":99}'));
        if ($event==='event.schedule' || $event==='event.leadwon') {
            $cases['exact-new-'.$type.'-'.$event]=array('exact',array('tipo'=>$type),json_encode($payload),array('{"result":true}','{"result":[]}','{"result":21}','{"result":[]}','{"result":42}','{"result":99}'));
        }
    }
}
return $cases;
