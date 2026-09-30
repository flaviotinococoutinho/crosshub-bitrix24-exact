<?php

$cases = array();
$cases['search-deal-found'] = array('bitrix', 'findDeal', array(array('UF_CRM_5C90EE1E02085' => '123')), array('{"result":[{"ID":"42"}]}'));
$cases['search-contact-found'] = array('bitrix', 'findContact', array(array('EMAIL' => 'person@example.invalid')), array('{"result":[{"ID":"21"}]}'));
$cases['search-deal-empty'] = array('bitrix', 'findDeal', array(array('ID' => '123')), array('{"result":[]}'));
$cases['search-contact-error'] = array('bitrix', 'findContact', array(array('ID' => '123')), array('{"error_description":"failure"}'));
$cases['create-contact'] = array('bitrix', 'createContact', array('Pessoa Exemplo','document','111','222','person@example.invalid','2020-01-02','public','feedback','123','code','notes','pre','vendas','Agência Alfa'), array('{"result":21}'));
$cases['create-contact-empty-phones'] = array('bitrix', 'createContact', array('Pessoa Exemplo','','','','person@example.invalid','','','','','','','','vendas',''), array('{"result":21}'));
$cases['update-lead'] = array('bitrix', 'updateLead', array('12','Empresa','Pessoa','person@example.invalid','111','222','public','code','pre','Agência Alfa','vendor@example.invalid','note','description','2020-01-02','03:04','feedback','ref','vendas','123','event.schedule'), array('{"result":true}'));
$cases['discard-lead'] = array('bitrix','discardLead',array('12','123'),array('{"result":true}'));
foreach (array('locacao alfa','locacao zeta','locacao gama','locacao epsilon','locacao beta','locacao delta','locacao especial','unknown') as $vendor) {
    foreach (array('event.schedule','event.reschedule','event.leadwon','event.cancelschedule','event.leadlost') as $event) {
        $cases['create-deal-'.$vendor.'-'.$event] = array('bitrix','createDeal',array('Pessoa Exemplo','21','123',$event,'vendas',$vendor,'pre','2020-01-02','code','feedback','public','notes'),array('{"result":42}'));
        $cases['update-deal-'.$vendor.'-'.$event] = array('bitrix','updateDeal',array('42',$event,$vendor,'2020-01-02'),array('{"result":true}'));
    }
}
$cases['list-event'] = array('bitrix','recordIntegrationEvent',array('111','person@example.invalid','sucesso','source','message'),array('{"result":99}'));
$cases['list-event-error'] = array('bitrix','recordIntegrationEvent',array('','','erro','',''),array('{"error_description":"failure"}'));
foreach (array('Aluguel','Vendas','Captacao Locacao','Captacao Vendas','Aluguel_Especial') as $type) {
    $cases['exact-add-'.$type] = array('exact','createLead',array('Empresa','person@example.invalid','Pessoa','111','222','code','12','notes',$type,'OLX',array()),array('{"success":true,"id":123}'));
    $cases['exact-update-'.$type] = array('exact','updateLead',array('123','Empresa','code','12','notes',$type,'OLX','111','222',array()),array('{"success":true}'));
    $cases['exact-get-'.$type] = array('exact','findLead',array('123',$type),array('{"Etapa":"Entrada"}'));
    $cases['exact-recover-'.$type] = array('exact','recoverLead',array('123',$type),array('{"success":true}'));
}
return $cases;
