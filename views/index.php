<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Aplikasi Daftar User</h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="index.php?action=create" class="btn btn-primary">+ Tambah User</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <a href="index.php?action=edit&id=<?= $user['id'] ?>"
                                    class="btn btn-sm btn-warning">Edit</a>
                                <a href="index.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data user.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>