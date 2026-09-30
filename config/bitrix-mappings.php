<?php

// IDs e estagios historicos; nomes de pessoas substituidos por exemplos.
// BTX_MAPPINGS_FILE pode selecionar o mapeamento privado sem alterar esta referencia.
return array(
    'createContact.enum_vendor_arr' => ["Agência Alfa"=>"2285","Locação - Alfa"=>"2285","Locação - Alfa Agência"=>"2285","Agência Beta"=>"2287","Locação - Beta Agência"=>"2287","Locação - Beta"=>"2287","Locação - Gama Agência"=>"2289","Locação - Gama Agência"=>"2289","Agência Gama"=>"2289","Locação - Gama"=>"2289","Locação - Delta Agência"=>"2291","Agência Delta"=>"2291","Locação - Delta"=>"2291","Locação - Epsilon Agência"=>"2293","Agência Epsilon"=>"2293","Locação - Epsilon"=>"2293","Agência Zeta"=>"2295","Locação - Zeta"=>"2295","Locação - Zeta Agência"=>"2295","Locacao - Zeta"=>"2295","Vendas"=>"7197","Especial"=>"2969","Agência Especial"=>"2969"],
    'createContact.tipo_lead_id_arr' => ["captacoes"=>"2955","vendas"=>"2953","locacao"=>"2951","aluguel_especial"=>"7091"],
    'updateLead.enum_vendor_arr' => ["Agência Alfa"=>"239","Locação - Alfa"=>"239","Locação - Alfa Agência"=>"239","Agência Beta"=>"241","Locação - Beta Agência"=>"241","Locação - Beta"=>"241","Locação - Gama Agência"=>"243","Locação - Gama Agência"=>"243","Agência Gama"=>"243","Locação - Gama"=>"243","Locação - Delta Agência"=>"245","Agência Delta"=>"245","Locação - Delta"=>"245","Locação - Epsilon Agência"=>"247","Agência Epsilon"=>"247","Locação - Epsilon"=>"247","Agência Zeta"=>"249","Locação - Zeta"=>"249","Locação - Zeta Agência"=>"249","Locacao - Zeta"=>"249","Vendas"=>"2963","Especial"=>"2963"],
    'updateLead.enum_status_agendamento' => ["event.schedule"=>"CONVERTED","event.cancelschedule"=>"4","event.leadlost"=>"4"],
    'updateLead.tipo_lead_id_arr' => ["captacoes"=>"2277","vendas"=>"2267","locacao"=>"2265","aluguel_especial"=>"7083"],
    'createDeal.enum_vendor_arr' => ["Agência Alfa"=>"899","Locação - Alfa"=>"899","Locação - Alfa Agência"=>"899","Agência Beta"=>"901","Locação - Beta Agência"=>"901","Locação - Beta"=>"901","Locação - Gama Agência"=>"903","Locação - Gama Agência"=>"903","Agência Gama"=>"903","Locação - Gama"=>"903","Locação - Delta Agência"=>"905","Agência Delta"=>"905","Locação - Delta"=>"905","Locação - Epsilon Agência"=>"907","Agência Epsilon"=>"907","Locação - Epsilon"=>"907","Agência Zeta"=>"909","Locação - Zeta"=>"909","Locação - Zeta Agência"=>"909","Locacao - Zeta"=>"909","Vendas"=>"7185","Especial"=>"2965","Agência Especial"=>"2965"],
    'createDeal.tipo_lead_id_arr' => ["captacoes"=>"2275","vendas"=>"2273","locacao"=>"2271","aluguel_especial"=>"7085"],
    'createDeal.stages_main' => array("event.schedule"=>"C129:NEW","event.reschedule"=>"C129:NEW","event.leadwon"=>"C129:WON","event.cancelschedule"=>"C129:LOSE","event.leadlost"=>"C129:LOSE"),
    'createDeal.category_status' => array (//event.leadwon
        "locacao alfa"=>array("status"=>["event.schedule"=>"C23:2","event.reschedule"=>"C23:2","event.leadwon"=>"C23:WON","event.cancelschedule"=>"C23:4","event.leadlost"=>"C23:4"],"category"=>"23"),
        "locacao zeta"=>array("status"=>["event.schedule"=>"C81:PREPARATION","event.reschedule"=>"C81:PREPARATION","event.leadwon"=>"C81:WON","event.cancelschedule"=>"C81:LOSE","event.leadlost"=>"C81:LOSE"],"category"=>"81"),
        "locacao gama"=>array("status"=>["event.schedule"=>"C75:PREPARATION","event.reschedule"=>"C75:PREPARATION","event.leadwon"=>"C75:WON","event.cancelschedule"=>"C75:LOSE","event.leadlost"=>"C75:LOSE"],"category"=>"75"),
        "locacao epsilon"=>array("status"=>["event.schedule"=>"C79:PREPARATION","event.reschedule"=>"C79:PREPARATION","event.leadwon"=>"C79:WON","event.cancelschedule"=>"C79:LOSE","event.leadlost"=>"C79:LOSE"],"category"=>"79"),
        "locacao beta"=>array("status"=>["event.schedule"=>"C73:PREPARATION","event.reschedule"=>"C73:PREPARATION","event.leadwon"=>"C73:WON","event.cancelschedule"=>"C73:LOSE","event.leadlost"=>"C73:LOSE"],"category"=>"73"),
        "locacao delta"=>array("status"=>["event.schedule"=>"C77:PREPARATION","event.reschedule"=>"C77:PREPARATION","event.leadwon"=>"C77:WON","event.cancelschedule"=>"C77:LOSE","event.leadlost"=>"C77:LOSE"],"category"=>"77"),
        "locacao especial"=>array("status"=>["event.schedule"=>"C121:NEW","event.reschedule"=>"C121:NEW","event.leadwon"=>"C121:WON","event.cancelschedule"=>"C121:LOSE","event.leadlost"=>"C121:LOSE"],"category"=>"121"),
        "vendedor exemplo alfa"=>array("status"=>["event.schedule"=>"C121:NEW","event.reschedule"=>"C121:NEW","event.leadwon"=>"C121:WON","event.cancelschedule"=>"C121:LOSE","event.leadlost"=>"C121:LOSE"],"category"=>"121"),
        "vendedora exemplo beta"=>array("status"=>["event.schedule"=>"C121:NEW","event.reschedule"=>"C121:NEW","event.leadwon"=>"C121:WON","event.cancelschedule"=>"C121:LOSE","event.leadlost"=>"C121:LOSE"],"category"=>"121"),
    ),
    'updateDeal.stages_main' => array("event.schedule"=>"C129:NEW","event.reschedule"=>"C129:NEW","event.leadwon"=>"C129:WON","event.cancelschedule"=>"C129:LOSE","event.leadlost"=>"C129:LOSE"),
    'updateDeal.category_status' => array (
        "locacao alfa"=>   ["event.schedule"=>"C23:2","event.reschedule"=>"C23:2","event.leadwon"=>"C23:WON","event.cancelschedule"=>"C23:4","event.leadlost"=>"C23:4"],
        "locacao zeta"=> ["event.schedule"=>"C81:PREPARATION","event.reschedule"=>"C81:PREPARATION","event.leadwon"=>"C81:WON","event.cancelschedule"=>"C81:LOSE","event.leadlost"=>"C81:LOSE"],
        "locacao gama"=> ["event.schedule"=>"C75:PREPARATION","event.reschedule"=>"C75:PREPARATION","event.leadwon"=>"C75:WON","event.cancelschedule"=>"C75:LOSE","event.leadlost"=>"C75:LOSE"],
        "locacao epsilon"=>  ["event.schedule"=>"C79:PREPARATION","event.reschedule"=>"C79:PREPARATION","event.leadwon"=>"C79:WON","event.cancelschedule"=>"C79:LOSE","event.leadlost"=>"C79:LOSE"],
        "locacao beta"=> ["event.schedule"=>"C73:PREPARATION","event.reschedule"=>"C73:PREPARATION","event.leadwon"=>"C73:WON","event.cancelschedule"=>"C73:LOSE","event.leadlost"=>"C73:LOSE"],
        "locacao delta"=>["event.schedule"=>"C77:PREPARATION","event.reschedule"=>"C77:PREPARATION","event.leadwon"=>"C77:WON","event.cancelschedule"=>"C77:LOSE","event.leadlost"=>"C77:LOSE"],
        "locacao especial"=>   ["event.schedule"=>"C121:NEW","event.reschedule"=>"C121:NEW","event.leadwon"=>"C121:WON","event.cancelschedule"=>"C121:LOSE","event.leadlost"=>"C121:LOSE"],
        "vendedor exemplo alfa"=>      ["event.schedule"=>"C121:NEW","event.reschedule"=>"C121:NEW","event.leadwon"=>"C121:WON","event.cancelschedule"=>"C121:LOSE","event.leadlost"=>"C121:LOSE"],
        "vendedora exemplo beta"=>          ["event.schedule"=>"C121:NEW","event.reschedule"=>"C121:NEW","event.leadwon"=>"C121:WON","event.cancelschedule"=>"C121:LOSE","event.leadlost"=>"C121:LOSE"],
    ),
    'recordIntegrationEvent.enum_status_event' => ["sucess"=>"155","sucesso"=>"155","error"=>"153","erro"=>"153","vazio"=>"159","aviso"=>"157"],
);
