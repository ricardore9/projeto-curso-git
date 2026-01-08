<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();

$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Fetch My Lists
$stmt = $pdo->prepare("
    SELECT sl.*,
    (SELECT COUNT(*) FROM list_items li WHERE li.list_id = sl.id) as total_items,
    (SELECT COUNT(*) FROM list_items li WHERE li.list_id = sl.id AND li.is_purchased = 1) as bought_items
    FROM shopping_lists sl
    WHERE sl.user_id = :user_id
    ORDER BY sl.created_at DESC
");
$stmt->execute(['user_id' => $user_id]);
$my_lists = $stmt->fetchAll();

// Fetch Shared Lists
$stmt = $pdo->prepare("
    SELECT sl.*, ls.permission_level, ls.status, u.username as owner_name,
    (SELECT COUNT(*) FROM list_items li WHERE li.list_id = sl.id) as total_items,
    (SELECT COUNT(*) FROM list_items li WHERE li.list_id = sl.id AND li.is_purchased = 1) as bought_items
    FROM shopping_lists sl
    JOIN list_shares ls ON sl.id = ls.list_id
    JOIN users u ON sl.user_id = u.id
    WHERE ls.user_id = :user_id
    ORDER BY ls.status DESC, sl.created_at DESC
");
$stmt->execute(['user_id' => $user_id]);
$all_shared_lists = $stmt->fetchAll();

$pending_lists = [];
$accepted_lists = [];

foreach ($all_shared_lists as $l) {
    if ($l['status'] == 'accepted') {
        $accepted_lists[] = $l;
    } else {
        $pending_lists[] = $l;
    }
}

// Helper to calculate progress
function getProgress($total, $bought) {
    if ($total == 0) return 0;
    return round(($bought / $total) * 100);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Listas - Lista de Materiais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Lista de Materiais</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Olá, <?php echo htmlspecialchars($username); ?></span>
                <a href="profile.php" class="btn btn-outline-light btn-sm me-2">Meu Perfil</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header & Add Button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Minhas Listas</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createListModal">
                <i class="fas fa-plus"></i> Nova Lista
            </button>
        </div>

        <!-- My Lists -->
        <div class="row">
            <?php if (empty($my_lists)): ?>
                <div class="col-12"><p class="text-muted">Você ainda não tem listas.</p></div>
            <?php else: ?>
                <?php foreach ($my_lists as $list): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100 list-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($list['title']); ?></h5>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($list['description']); ?></p>

                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: <?php echo getProgress($list['total_items'], $list['bought_items']); ?>%">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small mb-3">
                                    <span><?php echo $list['bought_items']; ?>/<?php echo $list['total_items']; ?> itens</span>
                                    <span><?php echo getProgress($list['total_items'], $list['bought_items']); ?>%</span>
                                </div>

                                <a href="list_details.php?id=<?php echo $list['id']; ?>" class="btn btn-primary w-100">Abrir Lista</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pending Shares -->
        <?php if (!empty($pending_lists)): ?>
            <h3 class="mt-4 mb-3 text-warning">Convites Pendentes</h3>
            <div class="row">
                <?php foreach ($pending_lists as $list): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100 border-warning">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($list['title']); ?></h5>
                                <p class="card-text text-muted small">
                                    <strong><?php echo htmlspecialchars($list['owner_name']); ?></strong> quer compartilhar esta lista com você.
                                </p>
                                <div class="d-flex gap-2">
                                    <button onclick="respondShare(<?php echo $list['id']; ?>, 'accept')" class="btn btn-success flex-grow-1">Aceitar</button>
                                    <button onclick="respondShare(<?php echo $list['id']; ?>, 'reject')" class="btn btn-outline-danger flex-grow-1">Recusar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Shared Lists -->
        <?php if (!empty($accepted_lists)): ?>
            <h3 class="mt-4 mb-3">Compartilhadas Comigo</h3>
            <div class="row">
                <?php foreach ($accepted_lists as $list): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100 border-info">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($list['title']); ?></h5>
                                <p class="card-text text-muted small">
                                    Dono: <strong><?php echo htmlspecialchars($list['owner_name']); ?></strong><br>
                                    Permissão: <?php echo $list['permission_level'] == 'edit' ? 'Edição' : 'Leitura'; ?>
                                </p>

                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-info" role="progressbar"
                                         style="width: <?php echo getProgress($list['total_items'], $list['bought_items']); ?>%">
                                    </div>
                                </div>

                                <a href="list_details.php?id=<?php echo $list['id']; ?>" class="btn btn-outline-info w-100">Abrir Lista</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Create List Modal -->
    <div class="modal fade" id="createListModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Nova Lista</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createListForm">
                        <div class="mb-3">
                            <label class="form-label">Nome da Lista</label>
                            <input type="text" id="listTitle" class="form-control" required placeholder="Ex: Material Escolar 2024">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição (Opcional)</label>
                            <textarea id="listDesc" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Criar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('createListForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const title = document.getElementById('listTitle').value;
            const description = document.getElementById('listDesc').value;

            fetch('api/create_list.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao criar lista: ' + data.message);
                }
            });
        });

        function respondShare(listId, action) {
            if (action === 'reject' && !confirm('Tem certeza que deseja recusar este convite?')) return;

            fetch('api/respond_share.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `list_id=${listId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
