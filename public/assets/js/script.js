var box = document.querySelector(".user-notification");


function show() {
    box.style.display = 'flex';
}

function hiden() {
    box.style.display = 'none';

}

document.addEventListener('DOMContentLoaded', function() {

    let ajax = new XMLHttpRequest();//instancia o ajax

    ajax.open('GET','http://localhost/bicorpIntegracao/public/dashboard');//envia requisição

    ajax.onreadystatechange = ()=>{

        if(ajax.readyState == 4 && ajax.status == 200){//verifica se a etapa é 4 concluida e o status é 200 ok

            let jsonTotalInvoices = ajax.responseText; //pega a resposta json em formato text
            let objJsonTotalInvoices = JSON.parse(jsonTotalInvoices);
            
            let totalInvoicesHML = objJsonTotalInvoices.totalInvoicesHML;
            let totalInvoicesMPR = objJsonTotalInvoices.totalInvoicesMPR;
            let totalInvoicesMSC = objJsonTotalInvoices.totalInvoicesMSC;
            let totalInvoices = objJsonTotalInvoices.totalInvoices;

            let totalOmieOrders = objJsonTotalInvoices.totalOmieOrders;
            let totalOmieOrdersHML = objJsonTotalInvoices.totalOmieOrdersHML;
            let totalOmieOrdersMPR = objJsonTotalInvoices.totalOmieOrdersMPR;
            let totalOmieOrdersMSC = objJsonTotalInvoices.totalOmieOrdersMSC;

            let totalDeals = objJsonTotalInvoices.totalDeals;

            let totalUsers = objJsonTotalInvoices.totalUsers;

            let targetInvoices = document.querySelector('#invoices > .content-box > .content-info > h3');
            let targetDeals = document.querySelector('#deals > .content-box > .content-info > h3');
            let targetUsers = document.querySelector('#users > .content-box > .content-info > h3');
            let targetOrders = document.querySelector('#omieOrders > .content-box > .content-info > h3');

            let targetInvoicesHML = document.querySelector('#totalInvoicesMHL');
            let targetInvoicesMPR = document.querySelector('#totalInvoicesMPR');
            let targetInvoicesMSC = document.querySelector('#totalInvoicesMSC');
            
            let targetOmieOrdersHML = document.querySelector('#totalOmieOrdersMHL');
            let targetOmieOrdersMPR = document.querySelector('#totalOmieOrdersMPR');
            let targetOmieOrdersMSC = document.querySelector('#totalOmieOrdersMSC');

            targetInvoices.innerHTML = totalInvoices;
            targetInvoicesHML.innerHTML = totalInvoicesHML;
            targetInvoicesMPR.innerHTML = totalInvoicesMPR;
            targetInvoicesMSC.innerHTML = totalInvoicesMSC;

            targetDeals.innerHTML = totalDeals;
            targetOmieOrdersHML.innerHTML = totalOmieOrdersHML;
            targetOmieOrdersMPR.innerHTML = totalOmieOrdersMPR;
            targetOmieOrdersMSC.innerHTML = totalOmieOrdersMSC;

            targetUsers.innerHTML = totalUsers;
            targetOrders.innerHTML = totalOmieOrders;
           
        }else{
            console.log(ajax.status);
        }
    }
    ajax.send();//dispara a requisição
    console.log('DOM completamente carregado e analisado');
});

// function getTotalInvoices(){
    

// }



  //  let imgUser = document.querySelector(".user-img");
  //  let btn = document.querySelector(".tipo-usuario");
  //  let link = document.querySelector(".logout"); 