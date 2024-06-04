<?php

namespace src\functions;

class DiverseFunctions{

    //CONVERTE PARA DATA EM PORTUGUÊS
    public static function convertDate($date)
    {

        $dateIni = explode('T', $date);
        $datePt = explode('-', $dateIni[0]);
        $datePtFn = implode("/", array_reverse($datePt)); // . " às " . $dateIni[1];

        return $datePtFn;
    }

    public static function convertDateHora($date)
    {

        $dateIni = explode('T', $date);
        $datePt = explode('-', $dateIni[0]);
        $datePtFn = implode("/", array_reverse($datePt)) . " às " . $dateIni[1];

        return $datePtFn;
    }

    public static function limpa_cpf_cnpj($valor){
        $valor = trim($valor);
        $valor = str_replace(array('.','-','/'), "", $valor);
        return $valor;
       }

    //MONTA TABELA EM HTML PRA ENVIAR A VIEW
    public static function montaTable($deal, $prop)
    {

        $html = "<table class='table-content' >";

        $html .= "<thead>";
        $html .= "<tr>";
        $html .= "<th>";
        $html .= "Action";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Entity";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "SecondaryEntityId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "AccountId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ActionUserId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WebhookId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WebhookCreatorId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "AttachmentsItems";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CollaboratingUsers";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Base Faturamento";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "TagId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "id";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Title";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ContactId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ContactName";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PersonId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PersonName";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PipelineId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StageId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StatusId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FirstTaskId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FirstTaskDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FirstTaskNoTime";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "HasScheduledTasks";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "TasksOrdination";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ContactProductId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastQuoteId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "IsLastQuoteApproved";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WonQuoteId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WonQuote";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastStageId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LossReasonId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "OriginId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "OwnerId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StartDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FinishDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CurrencyId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Amount";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StartCurrencyId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StartAmount";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Read";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastInteractionRecordId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastOrderId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "DaysInStage";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "HoursInStage";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Length";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CreateImportId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "UpdateImportId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LeadId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "OriginDealId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ReevId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CreatorId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "UpdaterId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CreateDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastUpdateDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastDocumentId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "DealNumber";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ImportationIdCreate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ImportationIdUpdate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PublicFormIdCreate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PublicFormIdUpdate";
        $html .= "</th>";
        $html .= "</tr>";
        $html .= "</thead>";

        $html .= "<tbody>";
        $html .= "<tr>";

        $html .= "<td>";
        $html .= $deal->action;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->entity;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->secondaryEntityId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->accountId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->actionUserId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->webhookId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->webhookCreatorId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->attachmentsItems ? $deal->attachmentsItems : '';
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->collaboratingUsers ? $deal->collaboratingUsers : '';
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->baseFaturamento;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->tags;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->id;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->title;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->contactId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->contactName;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->personId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->personName;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->pipelineId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->stageId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->statusId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->firstTaskId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->firstTaskDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->firstTaskNoTime;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->hasScheduledTasks;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->tasksOrdination;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->contactProductId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastQuoteId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->isLastQuoteApproved;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->wonQuoteId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->wonQuote;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastStageId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lossReasonId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->originId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->ownerId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->startDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->finishDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->currencyId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= "R$ " . number_format($deal->amount, 2, ',', '.');
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->startCurrencyId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= "R$ " . number_format($deal->startAmount, 2, ',', '.');
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->read;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastInteractionRecordId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastOrderId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->daysInStage;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->hoursInStage;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->length;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->createImportId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->updateImportId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->leadId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->originDealId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->reevId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->creatorId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->updaterId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->createDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->lastUpdateDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastDocumentId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->dealNumber;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->importationIdCreate;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->importationIdUpdate;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->publicFormIdCreate;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->publicFormIdUpdate;
        $html .= "</td>";

        $html .= "</tr>";
        $html .= "</tbody>";

        $html .= "</table>";
        //OTHER Properties
        if (!empty($prop)) {
            $html .= "<br/>";
            $html .= "<h2>Outras Propriedades</h2>";
            $html .= "<table class='table-content' border='1px'>";

            $html .= "<thead>";
            $html .= "<tr>";
            foreach ($prop as $key => $item) {
                $html .= "<th>";
                $html .= $key;
                $html .= "</th>";
            }
            $html .= "</tr>";
            $html .= "</thead>";
            $html .= "<tbody>";
            $html .= "<tr>";
            foreach ($prop as $itemProps) {
                $html .= "<td>";
                $html .= $itemProps;
                $html .= "</td>";
            }
            $html .= "</tr>";
            $html .= "</tbody>";
            $html .= "</table>";
        }

        if (!empty($deal->products)) {
        }
        return $html;
    }

}