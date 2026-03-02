<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$list_id = $_GET['id'];
$user_id = getCurrentUserId();

// Fetch List Details & Permission
$stmt = $pdo->prepare("
    SELECT sl.*, u.username as owner_name,
    CASE
        WHEN sl.user_id = :user_id THEN 'owner'
        ELSE (SELECT permission_level FROM list_shares WHERE list_id = sl.id AND user_id = :user_id)
    END as permission
    FROM shopping_lists sl
    JOIN users u ON sl.user_id = u.id
    WHERE sl.id = :list_id
");
$stmt->execute(['user_id' => $user_id, 'list_id' => $list_id]);
$list = $stmt->fetch();

// Check if user has access
if (!$list || !$list['permission']) {
    die("Acesso negado ou lista não encontrada.");
}

$can_edit = ($list['permission'] === 'owner' || $list['permission'] === 'edit');

// Fetch Items
$stmt = $pdo->prepare("SELECT * FROM list_items WHERE list_id = :list_id ORDER BY created_at ASC");
$stmt->execute(['list_id' => $list_id]);
$items = $stmt->fetchAll();

// Fetch Comments
$stmt = $pdo->prepare("
    SELECT lc.*, u.username
    FROM list_comments lc
    JOIN users u ON lc.user_id = u.id
    WHERE lc.list_id = :list_id
    ORDER BY lc.created_at ASC
");
$stmt->execute(['list_id' => $list_id]);
$comments = $stmt->fetchAll();

$total_price = 0;
foreach ($items as $item) {
    if ($item['is_purchased']) {
        $total_price += $item['price_paid'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($list['title']); ?> - Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lista de Materiais</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Voltar</a>
                <a href="profile.php" class="btn btn-outline-light btn-sm me-2">Meu Perfil</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?php echo htmlspecialchars($list['title']); ?></h3>
                    <small class="text-muted"><?php echo htmlspecialchars($list['description']); ?></small>
                </div>
                <div>
                    <span class="badge bg-success fs-6">Total Pago: R$ <span id="totalDisplay"><?php echo number_format($total_price, 2, ',', '.'); ?></span></span>
                    <button class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#emailModal">
                        <i class="fas fa-envelope"></i> E-mail
                    </button>
                    <?php if ($list['permission'] === 'owner'): ?>
                        <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#shareModal">
                            <i class="fas fa-share-alt"></i> Compartilhar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">

                <!-- Notes Section -->
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted">Observações:</label>
                    <textarea id="listNotes" class="form-control" rows="2"
                              <?php echo !$can_edit ? 'readonly' : ''; ?>
                              placeholder="<?php echo $can_edit ? 'Adicione observações aqui...' : 'Nenhuma observação.'; ?>"><?php echo htmlspecialchars($list['notes'] ?? ''); ?></textarea>
                    <?php if ($can_edit): ?>
                        <div id="notesStatus" class="form-text text-success d-none"><i class="fas fa-check"></i> Salvo</div>
                    <?php endif; ?>
                </div>

                <?php if ($can_edit): ?>
                    <!-- Add Item Form -->
                    <form id="addItemForm" class="row g-2 mb-4">
                        <input type="hidden" name="list_id" value="<?php echo $list_id; ?>">
                        <div class="col-md-6">
                            <input type="text" name="item_name" class="form-control" placeholder="Nome do Item (ex: Caderno)" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="quantity" class="form-control" placeholder="Qtd" value="1" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Adicionar</button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Items List -->
                <div class="list-group" id="itemsList">
                    <?php foreach ($items as $item): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between <?php echo $item['is_purchased'] ? 'bg-light' : ''; ?>" id="item-<?php echo $item['id']; ?>">
                            <div class="d-flex align-items-center flex-grow-1">
                                <?php if ($can_edit): ?>
                                    <input type="checkbox" class="form-check-input me-3 item-check"
                                           data-id="<?php echo $item['id']; ?>"
                                           <?php echo $item['is_purchased'] ? 'checked' : ''; ?>>
                                <?php else: ?>
                                    <i class="fas <?php echo $item['is_purchased'] ? 'fa-check-circle text-success' : 'fa-circle text-muted'; ?> me-3"></i>
                                <?php endif; ?>

                                <div>
                                    <h5 class="mb-0 <?php echo $item['is_purchased'] ? 'text-decoration-line-through text-muted' : ''; ?>">
                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                        <span class="badge bg-secondary rounded-pill ms-2"><?php echo $item['quantity']; ?></span>
                                    </h5>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <div class="input-group input-group-sm me-2" style="width: 150px;">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control item-price"
                                           data-id="<?php echo $item['id']; ?>"
                                           value="<?php echo $item['price_paid']; ?>"
                                           <?php echo (!$can_edit || !$item['is_purchased']) ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- Comments Section -->
            <div class="card-footer bg-light">
                <h5 class="mb-3">Comentários</h5>
                <div id="commentsList" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($comments as $msg): ?>
                        <div class="mb-2">
                            <strong><?php echo htmlspecialchars($msg['username']); ?>:</strong>
                            <span><?php echo nl2br(htmlspecialchars($msg['message'])); ?></span>
                            <br><small class="text-muted" style="font-size: 0.75rem;"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($comments)): ?>
                        <p class="text-muted small">Nenhum comentário ainda.</p>
                    <?php endif; ?>
                </div>
                <form id="commentForm" class="d-flex">
                    <input type="text" id="commentMsg" class="form-control me-2" placeholder="Escreva um comentário..." required>
                    <button type="submit" class="btn btn-secondary">Enviar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <div class="modal fade" id="emailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar por E-mail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="emailForm">
                        <div class="mb-3">
                            <label class="form-label">E-mail de Destino</label>
                            <input type="email" name="target_email" class="form-control" placeholder="Deixe em branco para usar seu e-mail" value="">
                            <div class="form-text">Se vazio, envia para seu e-mail cadastrado.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compartilhar Lista</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="shareForm">
                        <input type="hidden" name="list_id" value="<?php echo $list_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Usuário ou E-mail</label>
                            <input type="text" name="target_user" class="form-control" placeholder="Digite o nome de usuário ou e-mail exato" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permissão</label>
                            <select name="permission" class="form-select">
                                <option value="view">Visualizar</option>
                                <option value="edit">Editar</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Compartilhar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const listId = <?php echo $list_id; ?>;
        const canEdit = <?php echo $can_edit ? 'true' : 'false'; ?>;

        // Add Item
        if (document.getElementById('addItemForm')) {
            document.getElementById('addItemForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('api/add_item.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                });
            });
        }

        // Toggle Checkbox & Update Price
        document.querySelectorAll('.item-check').forEach(chk => {
            chk.addEventListener('change', function() {
                const itemId = this.dataset.id;
                const priceInput = document.querySelector(`.item-price[data-id="${itemId}"]`);
                const isChecked = this.checked;

                if (isChecked) {
                    priceInput.disabled = false;
                } else {
                    priceInput.disabled = true;
                    // Optional: reset price to 0 if unchecked? Maybe keep it for history.
                }

                updateItem(itemId, isChecked, priceInput.value);
            });
        });

        // Update Price on blur
        document.querySelectorAll('.item-price').forEach(input => {
            input.addEventListener('change', function() {
                const itemId = this.dataset.id;
                const checkbox = document.querySelector(`.item-check[data-id="${itemId}"]`);
                if (checkbox.checked) {
                    updateItem(itemId, true, this.value);
                }
            });
        });

        function updateItem(id, isPurchased, price) {
            fetch('api/update_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&is_purchased=${isPurchased ? 1 : 0}&price=${price}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update Total Display
                    document.getElementById('totalDisplay').textContent = data.new_total;

                    // Style updates
                    const row = document.getElementById(`item-${id}`);
                    const text = row.querySelector('h5');
                    if (isPurchased) {
                        row.classList.add('bg-light');
                        text.classList.add('text-decoration-line-through', 'text-muted');
                    } else {
                        row.classList.remove('bg-light');
                        text.classList.remove('text-decoration-line-through', 'text-muted');
                    }
                }
            });
        }

        // Auto-save Notes
        const notesInput = document.getElementById('listNotes');
        if (notesInput && !notesInput.readOnly) {
            let timeoutId;
            notesInput.addEventListener('input', function() {
                document.getElementById('notesStatus').classList.add('d-none');
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    fetch('api/update_notes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `list_id=${listId}&notes=${encodeURIComponent(this.value)}`
                    }).then(r => r.json()).then(d => {
                        if(d.success) document.getElementById('notesStatus').classList.remove('d-none');
                    });
                }, 1000);
            });
        }

        // Send Email
        const emailModal = document.getElementById('emailModal');
        if (emailModal) {
            document.getElementById('emailForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button');
                const originalText = btn.textContent;
                const email = this.querySelector('input[name="target_email"]').value;

                btn.disabled = true;
                btn.textContent = 'Enviando...';

                fetch('api/send_list_email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `list_id=${listId}&target_email=${encodeURIComponent(email)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('E-mail enviado com sucesso!');
                        const modal = bootstrap.Modal.getInstance(emailModal);
                        modal.hide();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
            });
        }

        // Comments
        document.getElementById('commentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const msgInput = document.getElementById('commentMsg');
            const msg = msgInput.value;

            fetch('api/add_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `list_id=${listId}&message=${encodeURIComponent(msg)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao enviar comentário.');
                }
            });
        });

        // Share List
        const shareModal = document.getElementById('shareModal');
        if (shareModal) {
            document.getElementById('shareForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('api/share_list.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Convite de compartilhamento enviado!');
                        const modal = bootstrap.Modal.getInstance(shareModal);
                        modal.hide();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                });
            });
        }
    </script>
</body>
</html>
