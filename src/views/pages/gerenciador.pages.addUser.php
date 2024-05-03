<div class="wrap">
    <?php $render('gerenciador.partials.head');?>
    <?php $render('gerenciador.partials.header',['loggedUser'=>$loggedUser]);?>
    <main>
    <?php $render('gerenciador.partials.aside');?>
    
        <section>
        <div class="title">
            <div class="area-title">
                <h2>Você está em: <?=$pagina?></h2>
            </div>
            <div class="area-filtro">
                <span>Usuários:</span>
                <a class="button" href="<?=$base?>/addUser"><span>Adiciona+</span></a>
                <!-- <span>Canais:</span>
                <a class="button" href=""><span>Filtrar</span><i class="material-icons btn">more_vert</i></a> -->
            </div>
        </div>
                <div class="content center-center">
                
                <div class="area-form">
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
                    <form method="post" enctype="multipart/form-data" action="<?=$base?>/addUser">
                        <div class="campo">
                            <label class="form-lbl" for="name">Nome:</label>
                            <input class="form-cmps" type="text" name="name" placeholder="Digite o nome do usuário" id="name">
                        </div>
                        <div class="campo">
                            <label class="form-lbl" for="email">E-mail</label>
                            <input class="form-cmps" type="text" name="email" placeholder="Digite o email do usuário" id="email">
                        </div>
                        <div class="campo">
                            <label class="form-lbl" for="pass">Senha</label>
                            <input class="form-cmps" type="password" name="pass" placeholder="Digite a senha do usuario" id="pass">
                        </div>
                        <div class="campo">
                            <label class="form-lbl" for="rpass">Repita a Senha</label>
                            <input class="form-cmps" type="password" name="rpass" placeholder="Repita a senha do usuario" id="rpass">
                        </div>
                        <div class="campo">
                            <label class="form-lbl" for="avatar">Avatar</label>
                            <input class="form-cmps" type="file" name="avatar" placeholder="Escolha uma imagem" id="avatar">
                        </div>
                        <div class="campo">
                            <label class="form-lbl" for="active">Ativo</label>
                            Sim - <input type="radio" name="active" id="active" value="1">
                            Não -<input type="radio" name="active" id="active" value="0">
                        </div>
                        <div class="campo">
                            <label class="form-lbl" for="type">Tipo de usuário</label>
                            Administrador - <input type="radio" name="type" id="type" value="1">
                            Redator -<input type="radio" name="type" id="type" value="2">
                        </div>

                        <button type="submit" class="btn-submit">Enviar</button>
                    </form>
                </div>
                </div>
        </section>
    </main>
    <?php $render('gerenciador.partials.footer');?>
</div>