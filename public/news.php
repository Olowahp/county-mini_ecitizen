<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$pageTitle = 'News and Public Notices';
$posts = $db->query("SELECT id,title,slug,category,summary,image_path,published_at,created_at FROM news_posts WHERE status='Published' ORDER BY COALESCE(published_at,created_at) DESC, id DESC")->fetchAll();
require dirname(__DIR__) . '/app/views/header.php';
?>
<div class="page-header">
    <div><span class="eyebrow">PUBLIC INFORMATION</span><h1>News & Public Notices</h1>
    <p>County announcements, service updates, public notices and community information.</p>
</div>
<?php if (is_admin_role(current_user()['role'] ?? null)): ?><a class="button primary" href="admin/news.php">Manage news →</a><?php endif; ?></div>
<div class="flag-banner" aria-hidden="true"></div>
<?php if (!$posts): ?><section class="panel empty-state"><div class="empty-icon">▤</div><h3>No announcements published yet</h3><p>Published county news and service notices will appear here.</p></section><?php else: ?>
<div class="news-grid">
    <?php foreach ($posts as $post): ?>
        <article class="news-card"><?php if ($post['image_path']): ?>
        <a href="news-view.php?slug=<?= rawurlencode($post['slug']) ?>">
            <img src="<?= e(url($post['image_path'])) ?>" alt="Image for <?= e($post['title']) ?>" loading="lazy"></a>
            <?php endif; ?>
            <div class="news-card-content"><span class="news-tag"><?= e($post['category']) ?></span>
            <h2><a href="news-view.php?slug=<?= rawurlencode($post['slug']) ?>"><?= e($post['title']) ?></a></h2><p><?= e($post['summary']) ?></p>
            <div class="news-meta"><?= e(date('d M Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></div><p style="margin-top:14px;margin-bottom:0"><a class="text-link" href="news-view.php?slug=<?= rawurlencode($post['slug']) ?>">Read announcement →</a></p></div>
        </article><?php endforeach; ?> </div>
            <?php endif; ?>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
