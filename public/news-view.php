<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = $db->prepare("SELECT * FROM news_posts WHERE slug=? AND status='Published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); $pageTitle = 'Announcement not found'; require dirname(__DIR__) . '/app/views/header.php'; echo '<section class="panel empty-state"><h2>Announcement not found</h2><p>This notice may have been removed or is not published.</p><a class="button primary" href="news.php">Back to news</a></section>'; require dirname(__DIR__) . '/app/views/footer.php'; exit; }
$pageTitle = $post['title'];
require dirname(__DIR__) . '/app/views/header.php';
?>
<article class="news-article"><?php if ($post['image_path']): ?><img src="<?= e(url($post['image_path'])) ?>" alt="Image for <?= e($post['title']) ?>"><?php endif; ?><div class="news-article-body"><a class="text-link" href="news.php">← All news and notices</a><p><span class="news-tag"><?= e($post['category']) ?></span></p><h1><?= e($post['title']) ?></h1><p class="news-meta">Published <?= e(date('d F Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></p><?php if ($post['summary']): ?><p><strong><?= e($post['summary']) ?></strong></p><?php endif; ?><div class="news-body"><?= e($post['body']) ?></div></div></article>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
