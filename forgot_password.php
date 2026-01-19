<?php require_once 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow mt-5">
            <div class="card-header bg-warning text-dark text-center">
                <h3 class="mb-0"><i class="fas fa-lock me-2"></i>Recuperar Senha</h3>
            </div>
            <div class="card-body p-4">
                <p class="text-center mb-4">Digite seu e-mail cadastrado para receber um link de redefinição de senha.</p>

                <form action="recover.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label fs-5">E-mail</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="seu@email.com">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg-custom">Enviar Link</button>
                        <a href="login.php" class="btn btn-secondary">Voltar para o Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
