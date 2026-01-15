<?php require_once 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow mt-5">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Entrar</h3>
            </div>
            <div class="card-body p-4">
                <form action="auth.php" method="POST">
                    <input type="hidden" name="action" value="login">

                    <div class="mb-3">
                        <label for="username" class="form-label fs-5">Usuário</label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="senha" class="form-label fs-5">Senha</label>
                        <input type="password" class="form-control form-control-lg" id="senha" name="senha" required>
                        <div class="text-end mt-1">
                            <a href="forgot_password.php" class="text-decoration-none small">Esqueci minha senha</a>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg-custom">Acessar Sistema</button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-2">Ainda não tem acesso?</p>
                    <a href="register.php" class="btn btn-success btn-lg fw-bold w-100 shadow-sm">Cadastrar Novo Líder</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
