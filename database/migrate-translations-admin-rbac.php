<?php
/**
 * migrate-translations-admin-rbac.php
 *
 * Terjemahan en/zh untuk RBAC panel admin (admin-access.php, admins.php,
 * sidebar Tim, flash penolakan akses).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-admin-rbac.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Admin berhasil dihapus.' => 'Admin deleted successfully.',
        'Admin berhasil diperbarui.' => 'Admin updated successfully.',
        'Admin berhasil ditambahkan.' => 'Admin added successfully.',
        'Anda' => 'You',
        'Anda tidak memiliki akses ke halaman ini.' => 'You do not have access to this page.',
        'Bersihkan' => 'Clear',
        'Biarkan kosong jika tidak diubah' => 'Leave empty to keep unchanged',
        'Dibuat' => 'Created',
        'Edit Admin' => 'Edit Admin',
        'Hak Akses' => 'Access Rights',
        'Kelola Admin' => 'Manage Admins',
        'Password minimal 6 karakter.' => 'Password must be at least 6 characters.',
        'Peran' => 'Role',
        'Pilih minimal satu halaman.' => 'Select at least one page.',
        'Preset' => 'Preset',
        'Semua akses' => 'All access',
        'Superadmin' => 'Superadmin',
        'Staff' => 'Staff',
        'Tambah Admin' => 'Add Admin',
        'Tidak dapat menghapus akun sendiri.' => 'You cannot delete your own account.',
        'Tidak dapat menghapus superadmin terakhir.' => 'Cannot delete the last superadmin.',
        'Tidak dapat menurunkan peran akun sendiri.' => 'You cannot demote your own account.',
        'Tidak dapat menurunkan superadmin terakhir.' => 'Cannot demote the last superadmin.',
        'Username 3-50 karakter (huruf, angka, _ . @ -).' => 'Username 3-50 characters (letters, numbers, _ . @ -).',
        'Username sudah dipakai.' => 'Username already taken.',
        'Yakin ingin menghapus akun ini?' => 'Are you sure you want to delete this account?',
        'hak akses dipilih' => 'selected access',
        'semua akses' => 'full access',
        'tanpa menu' => 'no menu',
    ],
    'zh' => [
        'Admin berhasil dihapus.' => '管理员已删除。',
        'Admin berhasil diperbarui.' => '管理员已更新。',
        'Admin berhasil ditambahkan.' => '已添加管理员。',
        'Anda' => '您',
        'Anda tidak memiliki akses ke halaman ini.' => '您无权访问此页面。',
        'Bersihkan' => '清除',
        'Biarkan kosong jika tidak diubah' => '如不修改请留空',
        'Dibuat' => '创建时间',
        'Edit Admin' => '编辑管理员',
        'Hak Akses' => '访问权限',
        'Kelola Admin' => '管理员管理',
        'Password minimal 6 karakter.' => '密码至少6个字符。',
        'Peran' => '角色',
        'Pilih minimal satu halaman.' => '请至少选择一个页面。',
        'Preset' => '预设',
        'Semua akses' => '全部权限',
        'Superadmin' => '超级管理员',
        'Staff' => '员工',
        'Tambah Admin' => '添加管理员',
        'Tidak dapat menghapus akun sendiri.' => '不能删除自己的账号。',
        'Tidak dapat menghapus superadmin terakhir.' => '不能删除最后一位超级管理员。',
        'Tidak dapat menurunkan peran akun sendiri.' => '不能降低自己账号的权限。',
        'Tidak dapat menurunkan superadmin terakhir.' => '不能降级最后一位超级管理员。',
        'Username 3-50 karakter (huruf, angka, _ . @ -).' => '用户名3-50个字符（字母、数字、_ . @ -）。',
        'Username sudah dipakai.' => '用户名已被使用。',
        'Yakin ingin menghapus akun ini?' => '确定要删除此账号吗？',
        'hak akses dipilih' => '按所选权限',
        'semua akses' => '全部权限',
        'tanpa menu' => '无菜单',
    ],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count admin-rbac translation rows.\n";
