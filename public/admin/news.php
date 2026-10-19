<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
$pageTitle = 'News and Public Notices';
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editPost = null;
if ($editId > 0) { $stmt=$db->prepare('SELECT * FROM news_posts WHERE id=?'); $stmt->execute([$editId]); $editPost=$stmt->fetch() ?: null; }
function news_slug(string $title): string { $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-')); return $slug !== '' ? $slug : 'notice'; }
function save_news_image(array $file, array $config): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('The image upload failed.');
    if (($file['size'] ?? 0) > $config['max_upload_bytes']) throw new RuntimeException('The image must be 5 MB or smaller.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($types[$mime])) throw new RuntimeException('Please upload a JPG, PNG or WebP image.');
    $name=bin2hex(random_bytes(16)).'.'.$types[$mime];
    if (!move_uploaded_file($file['tmp_name'], rtrim($config['news_upload_dir'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name)) throw new RuntimeException('The image could not be saved.');
    return 'uploads/news/'.$name;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $action=$_POST['action'] ?? '';
    if ($action === 'delete') {
        $id=(int)($_POST['id'] ?? 0); $stmt=$db->prepare('DELETE FROM news_posts WHERE id=?'); $stmt->execute([$id]); flash('success','News item deleted.'); redirect('/admin/news.php');
    }
    if ($action === 'save') {
        $id=(int)($_POST['id'] ?? 0); $title=trim((string)($_POST['title'] ?? '')); $category=(string)($_POST['category'] ?? 'News'); $summary=trim((string)($_POST['summary'] ?? '')); $body=trim((string)($_POST['body'] ?? '')); $status=(($_POST['status'] ?? 'Draft') === 'Published') ? 'Published' : 'Draft';
        if (strlen($title)<4) $errors[]='Enter a title with at least 4 characters.';
        if (strlen($body)<10) $errors[]='Enter the announcement details (at least 10 characters).';
        if (!in_array($category,['News','Public Notice','Service Update','Emergency Alert','Event'],true)) $category='News';
        $existing=null; if ($id) { $stmt=$db->prepare('SELECT * FROM news_posts WHERE id=?'); $stmt->execute([$id]); $existing=$stmt->fetch(); if (!$existing) $errors[]='The selected news item no longer exists.'; }
        $imagePath=$existing['image_path'] ?? null;
        if (!$errors) { try { $uploaded=save_news_image($_FILES['image'] ?? [],$config); if ($uploaded) $imagePath=$uploaded; } catch (RuntimeException $e) { $errors[]=$e->getMessage(); } }
        if (!$errors) {
            $base=news_slug($title); $slug=$base; $n=2; while (true) { $q=$db->prepare('SELECT id FROM news_posts WHERE slug=? AND id<>?'); $q->execute([$slug,$id]); $row = $q->fetch(); if (!$row) break; $slug=$base.'-'.$n++; }
            $publishedAt=$status==='Published' ? (($existing['published_at'] ?? null) ?: date('Y-m-d H:i:s')) : null;
            if ($id) { $stmt=$db->prepare('UPDATE news_posts SET title=?,slug=?,category=?,summary=?,body=?,image_path=?,status=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?'); $stmt->execute([$title,$slug,$category,$summary,$body,$imagePath,$status,$publishedAt,$id]); }
            else { $stmt=$db->prepare('INSERT INTO news_posts(title,slug,category,summary,body,image_path,status,published_at,created_by) VALUES(?,?,?,?,?,?,?,?,?)'); $stmt->execute([$title,$slug,$category,$summary,$body,$imagePath,$status,$publishedAt,(int)current_user()['id']]); }
            flash('success',$status==='Published'?'Announcement saved and published.':'Draft saved.'); redirect('/admin/news.php');
        }
        $editPost=['id'=>$id,'title'=>$title,'category'=>$category,'summary'=>$summary,'body'=>$body,'status'=>$status,'image_path'=>$imagePath];
    }
}
$posts=$db->query('SELECT n.*,u.name AS author_name FROM news_posts n LEFT JOIN users u ON u.id=n.created_by ORDER BY n.created_at DESC')->fetchAll();
require dirname(__DIR__, 2) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">PUBLIC COMMUNICATIONS</span><h1>News & Public Notices</h1>
    <p>Publish county announcements, service updates, notices and image-supported information.</p>
</div><a class="button ghost" href="news.php" target="_blank" rel="noopener">View public page ↗</a></div>
<div class="admin-nav">
    <a href="admin/index.php">Overview</a
    ><a href="admin/requests.php">All requests</a>
    <a href="admin/categories.php">Service categories</a>
    <a href="admin/users.php">Users and staff</a>
    <a href="admin/payments.php">Water payments</a>
    <a href="admin/business-licences.php">Business licences</a>
    <a class="active" href="admin/news.php">News & Notices</a>
</div>
<section class="panel">
    <div class="panel-heading">
        <div><h2><?= $editPost ? 'Edit announcement' : 'Create an announcement' ?></h2>
        <p>Save as draft or publish immediately to the public portal.</p>
    </div>
</div>
<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editPost['id'] ?? 0) ?>"><div class="news-form-grid"><label>Title<input name="title" required minlength="4" maxlength="180" value="<?= e($editPost['title'] ?? '') ?>" placeholder="e.g. Planned water supply interruption"></label><label>Category<select name="category"><?php foreach (['News','Public Notice','Service Update','Emergency Alert','Event'] as $cat): ?><option value="<?= e($cat) ?>" <?= (($editPost['category'] ?? 'News')===$cat)?'selected':'' ?>><?= e($cat) ?></option><?php endforeach; ?></select></label><label class="wide">Short summary <small>(optional, shown on news cards)</small><textarea name="summary" rows="2" maxlength="400" placeholder="Briefly describe the announcement."><?= e($editPost['summary'] ?? '') ?></textarea></label><label class="wide">Full announcement<textarea name="body" rows="8" required minlength="10" placeholder="Write the full notice, dates, affected areas and instructions."><?= e($editPost['body'] ?? '') ?></textarea></label><label>Attach an image <small>(optional; JPG, PNG or WebP, maximum 5 MB)</small><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><?php if (!empty($editPost['image_path'])): ?><small>Current image is retained unless a new image is uploaded.</small><?php endif; ?></label><label>Publication status<select name="status"><option value="Draft" <?= (($editPost['status'] ?? 'Draft')==='Draft')?'selected':'' ?>>Save as draft</option><option value="Published" <?= (($editPost['status'] ?? '')==='Published')?'selected':'' ?>>Publish now</option></select></label></div><div class="form-actions"><button class="button primary" type="submit">Save announcement →</button><?php if ($editPost): ?><a class="button ghost" href="admin/news.php">Cancel edit</a><?php endif; ?></div></form></section>
<section class="panel">
    <div class="panel-heading"><div>
        <h2>All announcements</h2>
        <p>Drafts are only visible to administrators; published items are public.</p>
    </div>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$posts): ?>
                <tr>
                    <td colspan="6" class="empty-cell">No news items yet. Create the first announcement above.</td></tr>
                <?php else: foreach ($posts as $post): ?>
                    <tr>
                    <td><?php if ($post['image_path']): ?><img class="news-thumb" src="<?= e(url($post['image_path'])) ?>" alt=""><?php else: ?>—<?php endif; ?></td>
                        <td><?= e($post['title']) ?></td>
                        <td><?= e($post['category']) ?></td>
                        <td>
                            <span class="status-pill <?= $post['status']==='Published'?'success':'neutral' ?>"><?= e($post['status']) ?></span>
                        </td>
                        <td><?= e(date('d M Y',strtotime($post['published_at'] ?: $post['created_at']))) ?></td>
                        <td><a class="table-link" href="admin/news.php?edit=<?= (int)$post['id'] ?>">Edit</a> 
                        <?php if ($post['status']==='Published'): ?>· 
                            <a class="table-link" href="news-view.php?slug=<?= rawurlencode($post['slug']) ?>" target="_blank" rel="noopener">View</a>
                            <?php endif; ?> · 
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this announcement?')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$post['id'] ?>"><button class="table-link" style="border:0;background:none;cursor:pointer" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php require dirname(__DIR__, 2) . '/app/views/footer.php'; ?>
