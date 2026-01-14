<?php
require_once 'includes/db.php';

// Buscar departamentos para o formulário
try {
    $stmt = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome ASC");
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $departamentos = [];
    $erro = "Não foi possível carregar os departamentos.";
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i>Cadastro de Líder</h3>
            </div>
            <div class="card-body p-4">
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger"><?php echo $erro; ?></div>
                <?php endif; ?>

                <form action="auth.php" method="POST">
                    <input type="hidden" name="action" value="register">

                    <div class="mb-3">
                        <label for="nome" class="form-label fs-5">Nome Completo</label>
                        <input type="text" class="form-control form-control-lg" id="nome" name="nome" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fs-5">E-mail</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="senha" class="form-label fs-5">Senha</label>
                            <input type="password" class="form-control form-control-lg" id="senha" name="senha" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirma_senha" class="form-label fs-5">Confirmar Senha</label>
                            <input type="password" class="form-control form-control-lg" id="confirma_senha" name="confirma_senha" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fs-5 d-block">Quais departamentos você lidera?</label>
                        <div class="card card-body bg-light">
                            <?php if (empty($departamentos)): ?>
                                <p class="text-muted">Nenhum departamento encontrado. Contate o administrador.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($departamentos as $dept): ?>
                                        <div class="col-sm-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="departamentos[]" value="<?php echo $dept['id']; ?>" id="dept_<?php echo $dept['id']; ?>">
                                                <label class="form-check-label fs-6" for="dept_<?php echo $dept['id']; ?>">
                                                    <?php echo htmlspecialchars($dept['nome']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text">Você pode selecionar mais de um departamento.</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg-custom">Cadastrar</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
