<div class="wrap">
    <?php $render('gerenciador.partials.head');?>
    <?php $render('gerenciador.partials.header',['loggedUser'=>$loggedUser]);?>
    <main>
    <?php $render('gerenciador.partials.aside',['loggedUser'=>$loggedUser]);?>
    
        <section>
        <div class="title">
            <div class="area-title">
                <h2>Você está em: <?=$pagina?></h2>
            </div>
            <div class="area-filtro">
                <span>Integrações:</span>
                <a class="button" href=""><span>Filtrar</span><i class="material-icons btn">more_vert</i></a>
                <!-- <span>Canais:</span>
                <a class="button" href=""><span>Filtrar</span><i class="material-icons btn">more_vert</i></a> -->
            </div>
        </div>
                <div class="content  ">
                <?php if(!empty($flash)):?>
                        <div class="area-flash flex">
                            <i class="material-symbols-outlined">
                            warning
                            </i>
                            <h4 class="text-flash">
                                <?=$flash;?>
                            </h4>
                        </div>
                    <?php endif;?>
                <!-- Início infoBox -->
                <div class="info-Box" id="invoices">
                    <div class="title">
                        <h2>Notas Integradas</h2>
                    </div>
                    <div class="content-box">
                        <div class="content-info">
                            <h3>?</h3>
                            <span>Total de Notas Integradas</span>
                            <a href="">Ir para monitoramento</a>
                        </div>
                    </div>
                </div>
                <!-- Fim infoBox -->
                <!-- Início infoBox -->
                <div class="info-Box" id="deals">
                    <div class="title">
                        <h2>Propostas Integradas</h2>
                    </div>
                    <div class="content-box">
                        <div class="content-info">
                            <h3>?</h3>
                            <span>Total de propostas integradas pelo sistema</span>
                            <a href="">Ir para monitoramento</a>
                        </div>
                    </div>
                </div>
                <!-- Fim infoBox -->
                <!-- Início infoBox -->
                <div class="info-Box" id="users">
                    <div class="title">
                        <h2>Usuários</h2>
                    </div>
                    <div class="content-box">
                        <div class="content-info">
                            <h3>?</h3>
                            <span>Total de usuários do sistema</span>
                            <a href="">Ir para monitoramento</a>
                        </div>
                    </div>
                </div>
                <!-- Fim infoBox -->
                <!-- Início infoBox -->
                <div class="info-Box" id="omieOrders">
                    <div class="title">
                        <h2>Pedidos no Omie ERP</h2>
                    </div>
                    <div class="content-box">
                        <div class="content-info">
                            <h3>?</h3>
                            <span>Total de pedidos criados no Omie ERP</span>
                            <a href="">Ir para monitoramento</a>
                        </div>
                    </div>
                </div>
                <!-- Fim infoBox -->
                </div>
        </section>
    </main>
    <?php $render('gerenciador.partials.footer');?>
</div>