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
            let totalInvoices = objJsonTotalInvoices.totalInvoices;
            let totalDeals = objJsonTotalInvoices.totalDeals;
            let totalUsers = objJsonTotalInvoices.totalUsers;

            let targetInvoices = document.querySelector('#invoices > .content-box > .content-info > h3');
            let targetDeals = document.querySelector('#deals > .content-box > .content-info > h3');
            let targetUsers = document.querySelector('#users > .content-box > .content-info > h3');
            
            targetInvoices.innerHTML = totalInvoices;
            targetDeals.innerHTML = totalDeals;
            targetUsers.innerHTML = totalUsers;
           
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