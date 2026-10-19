<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$pageTitle = 'Home';
$categories = $db->query("SELECT name, description FROM categories WHERE is_active = 1 ORDER BY CASE WHEN name = 'Water and Sanitation' THEN 0 WHEN name = 'Business and Trade' THEN 1 ELSE 2 END, name")->fetchAll();
$latestNews = $db->query("SELECT title,slug,category,summary,image_path,published_at,created_at FROM news_posts WHERE status='Published' ORDER BY COALESCE(published_at,created_at) DESC,id DESC LIMIT 3")->fetchAll();
require dirname(__DIR__) . '/app/views/header.php';
?>
<section class="hero home-services-hero">
    <div class="hero-copy">
        <span class="eyebrow">MURANG'A COUNTY · CITIZEN SERVICES</span>
        <h1>County services, <em>all in one place.</em></h1>
        <p>Find a county service, submit a request, apply for a business licence, make a water-service payment, or read the latest public notices.</p>
        <div class="hero-actions">
            <a class="button primary" href="<?= current_user() ? 'dashboard.php' : './login.php' ?>">Access services <span>→</span></a>
            <a class="button ghost" href="news.php">News & public notices</a>
        </div>
    </div>
</section>
<div class="flag-banner" aria-hidden="true"></div>
<section class="section-heading">
    <div><span class="eyebrow">SERVICE DIRECTORY</span><h2>All county services</h2><p>Select a service below to get started.</p></div>
</section>
<section class="service-grid home-service-directory">
    <article class="service-card featured-service">
        <span class="service-icon icon-0">⌁</span><h3>Water and Sanitation Payments</h3><p>Report meter faults, water supply issues, billing concerns, leaks and sewerage problems. Pay when a verified payment request is issued.</p>
        <a href="<?= current_user() ? 'request-create.php' : './login.php' ?>">Open water services <span>↗</span></a>
    </article>
    <article class="service-card featured-service">
        <span class="service-icon icon-1">▤</span><h3>Business Licences</h3><p>Apply for a new county business licence or renew an existing licence for the available licence term.</p>
        <a href="<?= current_user() ? 'business-licence.php' : './login.php' ?>">Register or renew <span>↗</span></a>
    </article>
    

<?php foreach ($categories as $i => $category): ?>
    <article class="service-card">
        <span class="service-icon icon-<?= $i % 6 ?>"><?= ['⌁','◉','♻','✚','▤','⌂'][$i % 6] ?></span>
        <h3><?= e($category['name']) ?></h3><p><?= e($category['description']) ?></p>
        <a href="<?= current_user() ? 'request-create.php' : 'register.php' ?>">Request service <span>↗</span></a>
    </article>
<?php endforeach; ?>
</section>
<?php if ($latestNews): ?>
    <section style="margin-top:48px">
        <div class="section-heading"><div><span class="eyebrow">STAY INFORMED</span>
        <h2>Latest news & public notices</h2><p>Important updates and announcements from the county.</p>
    </div><a class="text-link" href="news.php">All news and notices →</a></div>
    <div class="news-grid">
        <?php foreach ($latestNews as $item): ?><article class="news-card"><?php if ($item['image_path']): ?>
            <a href="news-view.php?slug=<?= rawurlencode($item['slug']) ?>"><img src="<?= e(url($item['image_path'])) ?>" alt="" loading="lazy"></a><?php endif; ?>
            <div class="news-card-content"><span class="news-tag"><?= e($item['category']) ?></span><h2>
                <a href="news-view.php?slug=<?= rawurlencode($item['slug']) ?>"><?= e($item['title']) ?></a></h2><p><?= e($item['summary']) ?></p>
                <div class="news-meta"><?= e(date('d M Y',strtotime($item['published_at'] ?: $item['created_at']))) ?></div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
