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
                <div class="content column ">
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
                <div class="info-Box">
                    <div class="title">
                        <h2>Total de Integrações</h2>
                    </div>
                    <div class="content-box">
                        <div class="content-info">
                            <h3><?=$total;?></h3>
                            <span>TMA: 0:00:00</span>
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