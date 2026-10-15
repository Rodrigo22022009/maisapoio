<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    $db = new PDO('sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'apoio-plus.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');
    createSchema($db);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? '';
    if ($method === 'POST' && $action === 'register') {
        registerUser($db, readJson());
    }
    if ($method === 'POST' && $action === 'login') {
        loginUser($db, readJson());
    }
    if ($method === 'POST' && $action === 'logout') {
        $_SESSION = [];
        session_destroy();
        sendJson(['ok' => true]);
    }
    if ($method === 'GET' && $action === 'me') {
        sendJson(['user' => currentUser()]);
    }
    if ($method === 'GET' && $action === 'users') {
        requireRole('admin');
        sendJson(['users' => $db->query('SELECT id, username, name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($method === 'GET' && $action === 'ratings') {
        $user = requireLogin();
        sendJson(['ratings' => loadRatings($db, $user)]);
    }
    if ($method === 'GET' && $action === 'requests') {
        $user = requireLogin();
        sendJson(['requests' => loadRequests($db, $user)]);
    }
    if ($method === 'POST' && $action === 'requests') {
        $user = requireRole('beneficiario');
        createSupportRequest($db, $user, readJson());
    }
    if ($method === 'PUT' && $action === 'requests') {
        requireRole('admin');
        updateSupportRequest($db, readJson());
    }
    if ($method === 'POST' && $action === 'ratings') {
        $user = requireRole('beneficiario');
        addRating($db, $user, readJson());
    }
    if ($method === 'PUT' && $action === 'users') {
        requireRole('admin');
        $payload = readJson();
        $status = $payload['status'] ?? '';
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Estado de aprovação inválido.', 400);
        }
        $userId = (int) ($payload['id'] ?? 0);
        $db->beginTransaction();
        $query = $db->prepare('SELECT id, name, email, phone, role, status FROM users WHERE id = ? AND role <> \'admin\'');
        $query->execute([$userId]);
        $user = $query->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new RuntimeException('Utilizador não encontrado.', 404);
        }
        if ($status === 'approved' && $user['status'] !== 'approved') {
            createProfileForUser($db, $user);
        }
        $update = $db->prepare('UPDATE users SET status = ? WHERE id = ? AND role <> \'admin\'');
        $update->execute([$status, $userId]);
        $db->commit();
        sendJson(['ok' => true]);
    }
    if ($method === 'DELETE' && $action === 'users') {
        requireRole('admin');
        $payload = readJson();
        $userId = (int) ($payload['id'] ?? 0);
        $db->beginTransaction();
        $query = $db->prepare('SELECT id, name, email, role FROM users WHERE id = ? AND role <> \'admin\'');
        $query->execute([$userId]);
        $user = $query->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new RuntimeException('Utilizador não encontrado ou não pode ser eliminado.', 404);
        }
        if ($user['role'] === 'beneficiario') {
            $delete = $db->prepare('DELETE FROM beneficiarios WHERE name = ? AND email = ?');
        } else {
            $delete = $db->prepare('DELETE FROM medicos WHERE name = ? AND email = ?');
        }
        $delete->execute([$user['name'], $user['email']]);
        $removeUser = $db->prepare('DELETE FROM users WHERE id = ? AND role <> \'admin\'');
        $removeUser->execute([$userId]);
        $db->commit();
        sendJson(['ok' => true]);
    }
    $sessionUser = requireLogin();
    if ($method === 'GET') {
        sendJson(loadData($db));
    }
    if ($method === 'PUT') {
        $payload = readJson();
        if ($sessionUser['role'] === 'beneficiario') {
            validateBeneficiaryWrite($db, $payload);
        }
        saveData($db, $payload);
        sendJson(loadData($db));
    }
    if ($method === 'DELETE') {
        if ($sessionUser['role'] === 'beneficiario') {
            throw new RuntimeException('Beneficiários não podem eliminar dados.', 403);
        }
        $db->beginTransaction();
        $db->exec('DELETE FROM apoios; DELETE FROM medicos; DELETE FROM beneficiarios;');
        $db->commit();
        sendJson(loadData($db));
    }

    throw new RuntimeException('Método não suportado.', 405);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $status = $error->getCode() >= 400 && $error->getCode() < 600 ? $error->getCode() : 500;
    http_response_code($status);
    echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
}

function createSchema(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS beneficiarios (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    birth TEXT DEFAULT '',
    priority TEXT NOT NULL,
    phone TEXT DEFAULT '',
    email TEXT DEFAULT '',
    address TEXT DEFAULT '',
    city TEXT DEFAULT '',
    need TEXT NOT NULL,
    emergency TEXT DEFAULT '',
    notes TEXT DEFAULT ''
);
CREATE TABLE IF NOT EXISTS medicos (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    spec TEXT NOT NULL,
    phone TEXT DEFAULT '',
    email TEXT DEFAULT '',
    city TEXT DEFAULT '',
    avail TEXT NOT NULL,
    reg TEXT DEFAULT '',
    areas TEXT DEFAULT ''
);
CREATE TABLE IF NOT EXISTS apoios (
    id INTEGER PRIMARY KEY,
    date TEXT NOT NULL,
    benef INTEGER NOT NULL,
    med INTEGER NOT NULL,
    type TEXT NOT NULL,
    status TEXT NOT NULL,
    notes TEXT DEFAULT '',
    FOREIGN KEY (benef) REFERENCES beneficiarios(id) ON DELETE CASCADE,
    FOREIGN KEY (med) REFERENCES medicos(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT DEFAULT '',
    role TEXT NOT NULL CHECK(role IN ('admin', 'beneficiario', 'medico')),
    status TEXT NOT NULL CHECK(status IN ('pending', 'approved', 'rejected')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    apoio_id INTEGER NOT NULL UNIQUE,
    benef INTEGER NOT NULL,
    med INTEGER NOT NULL,
    stars INTEGER NOT NULL CHECK(stars BETWEEN 1 AND 5),
    comment TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (apoio_id) REFERENCES apoios(id) ON DELETE CASCADE,
    FOREIGN KEY (benef) REFERENCES beneficiarios(id) ON DELETE CASCADE,
    FOREIGN KEY (med) REFERENCES medicos(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS support_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beneficiary_id INTEGER NOT NULL,
    subject TEXT NOT NULL,
    description TEXT NOT NULL,
    priority TEXT NOT NULL DEFAULT 'Média',
    status TEXT NOT NULL DEFAULT 'Pendente' CHECK(status IN ('Pendente', 'Em análise', 'Aceite', 'Concluído', 'Cancelado')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiarios(id) ON DELETE CASCADE
);
SQL);

    $ratingColumns = $db->query('PRAGMA table_info(ratings)')->fetchAll(PDO::FETCH_ASSOC);
    if (!array_filter($ratingColumns, static fn(array $column): bool => $column['name'] === 'comment')) {
        $db->exec("ALTER TABLE ratings ADD COLUMN comment TEXT NOT NULL DEFAULT ''");
    }

    $columns = $db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
    if (!array_filter($columns, static fn(array $column): bool => $column['name'] === 'phone')) {
        $db->exec("ALTER TABLE users ADD COLUMN phone TEXT DEFAULT ''");
    }

    if ((int) $db->query('SELECT COUNT(*) FROM beneficiarios')->fetchColumn() === 0) {
        seedDatabase($db);
    }
    if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $admin = $db->prepare('INSERT INTO users (username, password_hash, name, role, status) VALUES (?, ?, ?, ?, ?)');
        $admin->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Coordenação', 'admin', 'approved']);
    }
    syncApprovedProfiles($db);
}

function seedDatabase(PDO $db): void
{
    $beneficiarios = [
        [1, 'Maria da Silva', '1943-05-12', 'Alta', '912 345 678', 'maria@example.pt', 'Rua das Flores, 12', 'Figueira da Foz', 'Cuidados médicos', 'Ana Silva — 913 111 222', 'Necessita de acompanhamento regular.'],
        [2, 'João Santos', '1951-09-20', 'Média', '914 222 333', 'joao@example.pt', 'Rua Central, 45', 'Coimbra', 'Medicação', 'Pedro Santos — 914 555 666', 'Apoio na gestão da medicação.'],
        [3, 'António Costa', '1938-02-03', 'Baixa', '916 444 555', '', 'Av. Principal, 8', 'Montemor-o-Velho', 'Acompanhamento', '', 'Acompanhamento social.']
    ];
    $medicos = [
        [1, 'Dr. Ricardo Almeida', 'Medicina Geral e Familiar', '917 123 456', 'ricardo@voluntarios.pt', 'Figueira da Foz', 'Disponível', 'OM-12345', 'Idosos, acompanhamento'],
        [2, 'Dra. Sofia Martins', 'Medicina Interna', '918 222 333', 'sofia@voluntarios.pt', 'Coimbra', 'Disponível', 'OM-23456', 'Doença crónica, idosos'],
        [3, 'Dr. Miguel Ferreira', 'Cardiologia', '919 333 444', 'miguel@voluntarios.pt', 'Leiria', 'Indisponível', 'OM-34567', 'Cardiologia']
    ];
    $apoios = [
        [1, '2026-09-18T10:00', 1, 1, 'Consulta domiciliária', 'Agendado', 'Avaliação geral'],
        [2, '2026-09-19T14:30', 2, 2, 'Seguimento', 'Agendado', 'Rever medicação'],
        [3, '2026-09-10T11:00', 3, 1, 'Acompanhamento', 'Realizado', 'Visita de acompanhamento']
    ];

    $db->beginTransaction();
    $insert = $db->prepare('INSERT INTO beneficiarios VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($beneficiarios as $row) $insert->execute($row);
    $insert = $db->prepare('INSERT INTO medicos VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($medicos as $row) $insert->execute($row);
    $insert = $db->prepare('INSERT INTO apoios VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($apoios as $row) $insert->execute($row);
    $db->commit();
}

function loadData(PDO $db): array
{
    return [
        'beneficiarios' => $db->query('SELECT * FROM beneficiarios ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        'medicos' => $db->query('SELECT * FROM medicos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        'apoios' => $db->query('SELECT * FROM apoios ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        'ratings' => loadRatings($db, currentUser())
    ];
}

function loadRequests(PDO $db, array $user): array
{
    if ($user['role'] === 'beneficiario') {
        $query = $db->prepare('SELECT r.*, b.name AS beneficiary_name FROM support_requests r JOIN beneficiarios b ON b.id = r.beneficiary_id WHERE b.name = ? AND b.email = ? ORDER BY r.id DESC');
        $query->execute([$user['name'], $user['email']]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    return $db->query('SELECT r.*, b.name AS beneficiary_name FROM support_requests r JOIN beneficiarios b ON b.id = r.beneficiary_id ORDER BY r.id DESC')->fetchAll(PDO::FETCH_ASSOC);
}

function createSupportRequest(PDO $db, array $user, array $payload): never
{
    $subject = trim((string) ($payload['subject'] ?? ''));
    $description = trim((string) ($payload['description'] ?? ''));
    $priority = (string) ($payload['priority'] ?? 'Média');
    if ($subject === '' || $description === '' || !in_array($priority, ['Alta', 'Média', 'Baixa'], true)) {
        throw new RuntimeException('Preencha o assunto e a descrição do pedido.', 400);
    }
    $find = $db->prepare('SELECT id FROM beneficiarios WHERE name = ? AND email = ? LIMIT 1');
    $find->execute([$user['name'], $user['email']]);
    $beneficiaryId = (int) $find->fetchColumn();
    if ($beneficiaryId < 1) throw new RuntimeException('O perfil de beneficiário ainda não está disponível.', 403);
    $insert = $db->prepare('INSERT INTO support_requests (beneficiary_id, subject, description, priority) VALUES (?, ?, ?, ?)');
    $insert->execute([$beneficiaryId, $subject, $description, $priority]);
    sendJson(['ok' => true, 'request' => ['id' => (int) $db->lastInsertId()]]);
}

function updateSupportRequest(PDO $db, array $payload): never
{
    $id = (int) ($payload['id'] ?? 0);
    $status = (string) ($payload['status'] ?? '');
    if ($id < 1 || !in_array($status, ['Pendente', 'Em análise', 'Aceite', 'Concluído', 'Cancelado'], true)) {
        throw new RuntimeException('Pedido ou estado inválido.', 400);
    }
    $update = $db->prepare('UPDATE support_requests SET status = ? WHERE id = ?');
    $update->execute([$status, $id]);
    if ($update->rowCount() < 1) throw new RuntimeException('Pedido não encontrado.', 404);
    sendJson(['ok' => true]);
}

function loadRatings(PDO $db, ?array $user): array
{
    if ($user !== null && $user['role'] === 'beneficiario') {
        $query = $db->prepare('SELECT r.* FROM ratings r JOIN beneficiarios b ON b.id = r.benef WHERE b.name = ? AND b.email = ? ORDER BY r.id');
        $query->execute([$user['name'], $user['email']]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    return $db->query('SELECT * FROM ratings ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
}

function addRating(PDO $db, array $user, array $payload): never
{
    $supportId = (int) ($payload['apoio_id'] ?? 0);
    $stars = (int) ($payload['stars'] ?? 0);
    $comment = trim((string) ($payload['comment'] ?? ''));
    if ($supportId < 1 || $stars < 1 || $stars > 5 || $comment === '') {
        throw new RuntimeException('Escolha entre 1 e 5 estrelas e escreva um comentário.', 400);
    }
    $query = $db->prepare('SELECT a.id, a.benef, a.med, a.status, b.name, b.email FROM apoios a JOIN beneficiarios b ON b.id = a.benef WHERE a.id = ?');
    $query->execute([$supportId]);
    $support = $query->fetch(PDO::FETCH_ASSOC);
    if (!$support || $support['status'] !== 'Realizado' || $support['name'] !== $user['name'] || $support['email'] !== $user['email']) {
        throw new RuntimeException('Só pode avaliar uma consulta realizada sua.', 403);
    }
    try {
        $insert = $db->prepare('INSERT INTO ratings (apoio_id, benef, med, stars, comment) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$support['id'], $support['benef'], $support['med'], $stars, $comment]);
    } catch (PDOException $error) {
        if ((int) $error->errorInfo[1] === 19) {
            throw new RuntimeException('Esta consulta já foi classificada.', 409);
        }
        throw $error;
    }
    sendJson(['ok' => true]);
}

function saveData(PDO $db, array $data): void
{
    foreach (['beneficiarios', 'medicos', 'apoios'] as $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            throw new RuntimeException("Dados inválidos: {$key}.", 400);
        }
    }

    $db->beginTransaction();
    $db->exec('DELETE FROM apoios; DELETE FROM medicos; DELETE FROM beneficiarios;');

    $insert = $db->prepare('INSERT INTO beneficiarios VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($data['beneficiarios'] as $row) {
        $insert->execute([(int) $row['id'], $row['name'] ?? '', $row['birth'] ?? '', $row['priority'] ?? 'Média', $row['phone'] ?? '', $row['email'] ?? '', $row['address'] ?? '', $row['city'] ?? '', $row['need'] ?? '', $row['emergency'] ?? '', $row['notes'] ?? '']);
    }
    $insert = $db->prepare('INSERT INTO medicos VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($data['medicos'] as $row) {
        $insert->execute([(int) $row['id'], $row['name'] ?? '', $row['spec'] ?? '', $row['phone'] ?? '', $row['email'] ?? '', $row['city'] ?? '', $row['avail'] ?? 'Disponível', $row['reg'] ?? '', $row['areas'] ?? '']);
    }
    $insert = $db->prepare('INSERT INTO apoios VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($data['apoios'] as $row) {
        $insert->execute([(int) $row['id'], $row['date'] ?? '', (int) $row['benef'], (int) $row['med'], $row['type'] ?? '', $row['status'] ?? 'Agendado', $row['notes'] ?? '']);
    }
    $db->commit();
}

function sendJson(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function createProfileForUser(PDO $db, array $user): void
{
    if ($user['role'] === 'beneficiario') {
        $find = $db->prepare('SELECT id FROM beneficiarios WHERE name = ? AND email = ? LIMIT 1');
        $find->execute([$user['name'], $user['email']]);
        if (!$find->fetchColumn()) {
            $id = (int) $db->query('SELECT COALESCE(MAX(id), 0) + 1 FROM beneficiarios')->fetchColumn();
            $insert = $db->prepare('INSERT INTO beneficiarios (id, name, birth, priority, phone, email, address, city, need, emergency, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$id, $user['name'], '', 'Média', $user['phone'] ?? '', $user['email'], '', '', 'Acompanhamento', '', 'Conta aprovada; completar dados do beneficiário.']);
        }
        return;
    }
    if ($user['role'] === 'medico') {
        $find = $db->prepare('SELECT id FROM medicos WHERE name = ? AND email = ? LIMIT 1');
        $find->execute([$user['name'], $user['email']]);
        if (!$find->fetchColumn()) {
            $id = (int) $db->query('SELECT COALESCE(MAX(id), 0) + 1 FROM medicos')->fetchColumn();
            $insert = $db->prepare('INSERT INTO medicos (id, name, spec, phone, email, city, avail, reg, areas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$id, $user['name'], 'Por definir', $user['phone'] ?? '', $user['email'], '', 'Disponível', '', '']);
        }
    }
}

function validateBeneficiaryWrite(PDO $db, array $payload): void
{
    foreach (['beneficiarios', 'medicos', 'apoios'] as $key) {
        if (!isset($payload[$key]) || !is_array($payload[$key])) {
            throw new RuntimeException("Dados inválidos: {$key}.", 400);
        }
    }
    $current = loadData($db);
    $user = currentUser();
    $beneficiaryQuery = $db->prepare('SELECT id FROM beneficiarios WHERE name = ? AND email = ? LIMIT 1');
    $beneficiaryQuery->execute([$user['name'], $user['email']]);
    $beneficiaryId = (int) $beneficiaryQuery->fetchColumn();
    if ($beneficiaryId < 1) {
        throw new RuntimeException('O perfil de beneficiário ainda não está disponível.', 403);
    }
    $currentMedicos = array_map(static function (array $medico): array {
        if (($medico['avail'] ?? '') === 'Indisponível') {
            $medico['avail'] = 'Em atendimento';
        }
        return $medico;
    }, $current['medicos']);
    if ($payload['beneficiarios'] != $current['beneficiarios'] || $payload['medicos'] != $currentMedicos) {
        throw new RuntimeException('Beneficiários não podem alterar perfis.', 403);
    }
    $existing = [];
    foreach ($current['apoios'] as $support) {
        $existing[(int) $support['id']] = $support;
    }
    foreach ($payload['apoios'] as $support) {
        $id = (int) ($support['id'] ?? 0);
        if (isset($existing[$id]) && $support != $existing[$id]) {
            throw new RuntimeException('Beneficiários não podem alterar o estado ou os comentários das consultas.', 403);
        }
        if (!isset($existing[$id])) {
            if ((int) ($support['benef'] ?? 0) !== $beneficiaryId) {
                throw new RuntimeException('Só pode agendar consultas para o seu próprio perfil.', 403);
            }
            $med = $db->prepare('SELECT COUNT(*) FROM medicos WHERE id = ? AND avail = \'Disponível\'');
            $med->execute([(int) ($support['med'] ?? 0)]);
            if ((int) $med->fetchColumn() !== 1) {
                throw new RuntimeException('Escolha um médico disponível.', 400);
            }
        }
    }
    if (count($payload['apoios']) < count($current['apoios'])) {
        throw new RuntimeException('Beneficiários não podem eliminar consultas.', 403);
    }
}

function syncApprovedProfiles(PDO $db): void
{
    $users = $db->query("SELECT id, name, email, phone, role, status FROM users WHERE status = 'approved' AND role <> 'admin'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        createProfileForUser($db, $user);
    }
}

function readJson(): array
{
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        throw new RuntimeException('JSON inválido.', 400);
    }
    return $payload;
}

function currentUser(): ?array
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }
    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
        $_SESSION = [];
        session_destroy();
        return null;
    }
    $_SESSION['last_activity'] = time();
    return $_SESSION['user'];
}

function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        throw new RuntimeException('É necessário iniciar sessão.', 401);
    }
    return $user;
}

function requireRole(string $role): array
{
    $user = requireLogin();
    if (($user['role'] ?? '') !== $role) {
        throw new RuntimeException('Sem permissões para esta operação.', 403);
    }
    return $user;
}

function registerUser(PDO $db, array $payload): never
{
    $username = strtolower(trim((string) ($payload['username'] ?? '')));
    $password = (string) ($payload['password'] ?? '');
    $name = trim((string) ($payload['name'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $phone = trim((string) ($payload['phone'] ?? ''));
    $role = (string) ($payload['role'] ?? '');
    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $username) || strlen($password) < 8 || $name === '' || !in_array($role, ['beneficiario', 'medico'], true)) {
        throw new RuntimeException('Preencha os dados corretamente. A palavra-passe deve ter pelo menos 8 caracteres.', 400);
    }
    try {
        $insert = $db->prepare('INSERT INTO users (username, password_hash, name, email, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $email, $phone, $role, 'pending']);
    } catch (PDOException $error) {
        if ((int) $error->errorInfo[1] === 19) {
            throw new RuntimeException('Esse nome de utilizador já existe.', 409);
        }
        throw $error;
    }
    sendJson(['ok' => true, 'message' => 'Conta criada. Aguarde aprovação da coordenação.']);
}

function loginUser(PDO $db, array $payload): never
{
    $username = strtolower(trim((string) ($payload['username'] ?? '')));
    $password = (string) ($payload['password'] ?? '');
    $query = $db->prepare('SELECT id, username, password_hash, name, email, role, status FROM users WHERE username = ?');
    $query->execute([$username]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        throw new RuntimeException('Utilizador ou palavra-passe inválidos.', 401);
    }
    if ($user['status'] !== 'approved') {
        throw new RuntimeException($user['status'] === 'pending' ? 'A conta aguarda aprovação da coordenação.' : 'Esta conta foi recusada.', 403);
    }
    unset($user['password_hash']);
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    $_SESSION['last_activity'] = time();
    sendJson(['ok' => true, 'user' => $user]);
}
