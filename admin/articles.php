<?php
/**
 * EstateVista India - Market Intelligence & Editorial Management (Module 15)
 * Manage research reports, luxury real estate forecasts, and industry analysis articles.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Create Article
        if ($action === 'create_article') {
            $title = sanitize($_POST['title'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $category = sanitize($_POST['category'] ?? 'Market Trends');
            $read_time = sanitize($_POST['read_time'] ?? '5 min read');
            $author = sanitize($_POST['author'] ?? 'EstateVista Intelligence Desk');
            $publish_date = sanitize($_POST['publish_date'] ?? date('d M Y'));
            $image_url = sanitize($_POST['image_url'] ?? '');
            $excerpt = sanitize($_POST['excerpt'] ?? '');
            $content = $_POST['content'] ?? '';
            $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

            if (empty($title) || empty($excerpt)) {
                $error_msg = "Article title and executive summary/excerpt are required.";
            } else {
                if (empty($slug)) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
                }
                if (empty($image_url)) {
                    $image_url = 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80';
                }

                try {
                    $chk = $db->prepare("SELECT id FROM articles WHERE slug = :s LIMIT 1");
                    $chk->execute([':s' => $slug]);
                    if ($chk->fetch()) {
                        $error_msg = "An article with this slug already exists.";
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO articles (title, slug, category, read_time, author, publish_date, image_url, excerpt, content, status, created_at)
                            VALUES (:title, :slug, :cat, :rt, :auth, :pdate, :img, :exc, :cnt, :st, NOW())
                        ");
                        $stmt->execute([
                            ':title' => $title,
                            ':slug' => $slug,
                            ':cat' => $category,
                            ':rt' => $read_time,
                            ':auth' => $author,
                            ':pdate' => $publish_date,
                            ':img' => $image_url,
                            ':exc' => $excerpt,
                            ':cnt' => $content,
                            ':st' => $status
                        ]);
                        log_admin_activity('Created Market Intelligence Article', "Published research piece: '{$title}'");
                        $success_msg = "Article '{$title}' saved successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Edit Article
        elseif ($action === 'edit_article') {
            $art_id = (int)($_POST['article_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $category = sanitize($_POST['category'] ?? 'Market Trends');
            $read_time = sanitize($_POST['read_time'] ?? '5 min read');
            $author = sanitize($_POST['author'] ?? '');
            $publish_date = sanitize($_POST['publish_date'] ?? '');
            $image_url = sanitize($_POST['image_url'] ?? '');
            $excerpt = sanitize($_POST['excerpt'] ?? '');
            $content = $_POST['content'] ?? '';
            $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

            if ($art_id <= 0 || empty($title) || empty($excerpt)) {
                $error_msg = "Article ID, title, and excerpt are required.";
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE articles
                        SET title = :title, slug = :slug, category = :cat, read_time = :rt,
                            author = :auth, publish_date = :pdate, image_url = :img,
                            excerpt = :exc, content = :cnt, status = :st
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':title' => $title,
                        ':slug' => $slug,
                        ':cat' => $category,
                        ':rt' => $read_time,
                        ':auth' => $author,
                        ':pdate' => $publish_date,
                        ':img' => $image_url,
                        ':exc' => $excerpt,
                        ':cnt' => $content,
                        ':st' => $status,
                        ':id' => $art_id
                    ]);
                    log_admin_activity('Updated Article', "Updated article #{$art_id} ({$title})");
                    $success_msg = "Article updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Toggle Status
        elseif ($action === 'toggle_status') {
            $art_id = (int)($_POST['article_id'] ?? 0);
            $new_status = in_array($_POST['new_status'] ?? '', ['published', 'draft']) ? $_POST['new_status'] : 'published';
            if ($art_id > 0) {
                try {
                    $stmt = $db->prepare("UPDATE articles SET status = :s WHERE id = :id");
                    $stmt->execute([':s' => $new_status, ':id' => $art_id]);
                    log_admin_activity('Toggled Article Status', "Article #{$art_id} set to {$new_status}");
                    $success_msg = "Article status set to " . ucfirst($new_status) . ".";
                } catch (PDOException $e) {
                    $error_msg = "Error updating status: " . $e->getMessage();
                }
            }
        }

        // 4. Delete Article
        elseif ($action === 'delete_article') {
            $art_id = (int)($_POST['article_id'] ?? 0);
            if ($art_id > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM articles WHERE id = :id");
                    $stmt->execute([':id' => $art_id]);
                    log_admin_activity('Deleted Article', "Deleted article #{$art_id}");
                    $success_msg = "Article removed successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Error deleting article: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Articles
$articles = $db->query("SELECT * FROM articles ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$total_articles = count($articles);
$published_count = count(array_filter($articles, fn($a) => ($a['status'] ?? 'published') === 'published'));

$page_title = "Market Intelligence & Articles";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Market Intelligence & Research Articles
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Editorial desk managing real estate forecasts, architectural insights, and wealth preservation analyses.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalAddArticle">
                <i class="fa-solid fa-feather-pointed"></i> Draft New Intelligence Brief
            </button>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: #ECFDF5; border-left: 4px solid var(--adm-success); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #065F46; font-weight: 500;">
                <i class="fa-solid fa-circle-check"></i> <?= e($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: #FEF2F2; border-left: 4px solid var(--adm-danger); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #991B1B; font-weight: 500;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- KPI Summary -->
        <div class="adm-kpi-grid">
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $total_articles ?></div>
                    <div class="adm-kpi-label">Total Articles & Reports</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-newspaper"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #059669;"><?= $published_count ?></div>
                    <div class="adm-kpi-label">Published Live</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #D97706;"><?= $total_articles - $published_count ?></div>
                    <div class="adm-kpi-label">Drafts / In Review</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-file-pen"></i>
                </div>
            </div>
        </div>

        <!-- Articles Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-book-open-reader" style="color: var(--adm-gold);"></i>
                    Published Publications (<?= count($articles) ?>)
                </div>
                <div class="adm-search-input-wrap" style="min-width: 250px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" class="adm-input" data-adm-table-search="tableArticles" placeholder="Filter articles by title...">
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table" id="tableArticles">
                    <thead>
                        <tr>
                            <th>Cover & Article Title</th>
                            <th>Category</th>
                            <th>Author / Desk</th>
                            <th>Read Time</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    No editorial pieces found. Click "Draft New Intelligence Brief" to create one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($articles as $a): ?>
                                <?php $a_status = $a['status'] ?? 'published'; ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                                            <img src="<?= e(safe_image_url($a['image_url'], 'article')) ?>" alt="<?= e($a['title']) ?>"
                                                 style="width: 60px; height: 42px; border-radius: 6px; object-fit: cover; border: 1px solid var(--adm-border);"
                                                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                            <div>
                                                <div style="font-weight: 700; color: var(--adm-navy); max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?= e($a['title']) ?>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--adm-text-muted);">Slug: <?= e($a['slug']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="adm-status-badge adm-badge-new" style="background: #F1F5F9; color: #1E3A8A; font-weight: 700;">
                                            <?= e($a['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--adm-navy);"><?= e($a['author']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.8rem; color: var(--adm-text-muted);"><i class="fa-regular fa-clock"></i> <?= e($a['read_time']) ?></div>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--adm-text-muted);">
                                        <?= e($a['publish_date']) ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="articles.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="article_id" value="<?= $a['id'] ?>">
                                            <?php if ($a_status === 'published'): ?>
                                                <input type="hidden" name="new_status" value="draft">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-success" title="Click to Unpublish">
                                                    <i class="fa-solid fa-check"></i> Published
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_status" value="published">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" title="Click to Publish">
                                                    Draft
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 0.35rem;">
                                            <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-edit-article"
                                                data-id="<?= $a['id'] ?>"
                                                data-title="<?= e($a['title']) ?>"
                                                data-slug="<?= e($a['slug']) ?>"
                                                data-cat="<?= e($a['category']) ?>"
                                                data-auth="<?= e($a['author']) ?>"
                                                data-time="<?= e($a['read_time']) ?>"
                                                data-date="<?= e($a['publish_date']) ?>"
                                                data-img="<?= e($a['image_url']) ?>"
                                                data-exc="<?= e($a['excerpt']) ?>"
                                                data-status="<?= e($a_status) ?>"
                                                title="Edit Article">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <form method="POST" action="articles.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                <input type="hidden" name="action" value="delete_article">
                                                <input type="hidden" name="article_id" value="<?= $a['id'] ?>">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="Article '<?= e($a['title']) ?>'" title="Delete Article">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Add Article -->
<div class="adm-modal-backdrop" id="modalAddArticle">
    <div class="adm-modal" style="max-width: 650px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-feather-pointed" style="color: var(--adm-gold);"></i> Draft Market Intelligence Brief</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="articles.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_article">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Article Headline / Title *</label>
                    <input type="text" name="title" class="adm-input" required placeholder="e.g. Ultra-Luxury Residential Inflows: Mumbai & Delhi FY27 Outlook">
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Category</label>
                        <select name="category" class="adm-select">
                            <option value="Market Trends">Market Trends</option>
                            <option value="Investment Intelligence">Investment Intelligence</option>
                            <option value="Regulatory & Tax">Regulatory & Tax (RERA/RBI)</option>
                            <option value="Architecture & Design">Architecture & Design</option>
                            <option value="HNW Lifestyle">HNW Lifestyle</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Author Byline</label>
                        <input type="text" name="author" class="adm-input" value="EstateVista Intelligence Desk">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Estimated Read Time</label>
                        <input type="text" name="read_time" class="adm-input" value="5 min read">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Publication Date</label>
                        <input type="text" name="publish_date" class="adm-input" value="<?= date('d M Y') ?>">
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Cover Image URL</label>
                    <input type="url" name="image_url" class="adm-input" placeholder="https://images.unsplash.com/...">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Executive Summary / Excerpt *</label>
                    <textarea name="excerpt" class="adm-textarea" rows="2" required placeholder="Two-line high-impact executive summary for card displays..."></textarea>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Article Content (Markdown or HTML)</label>
                    <textarea name="content" class="adm-textarea" rows="4" placeholder="Full article narrative, analysis points, macroeconomic drivers, and advisory conclusions..."></textarea>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Publication Status</label>
                    <select name="status" class="adm-select">
                        <option value="published">Published Live</option>
                        <option value="draft">Save as Draft</option>
                    </select>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Publish Brief</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Article -->
<div class="adm-modal-backdrop" id="modalEditArticle">
    <div class="adm-modal" style="max-width: 650px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--adm-gold);"></i> Edit Publication</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="articles.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="edit_article">
            <input type="hidden" name="article_id" id="edit_art_id">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Article Title *</label>
                    <input type="text" name="title" id="edit_art_title" class="adm-input" required>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Category</label>
                        <input type="text" name="category" id="edit_art_cat" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Author</label>
                        <input type="text" name="author" id="edit_art_auth" class="adm-input">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Read Time</label>
                        <input type="text" name="read_time" id="edit_art_time" class="adm-input">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Publication Date</label>
                        <input type="text" name="publish_date" id="edit_art_date" class="adm-input">
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Cover Image URL</label>
                    <input type="url" name="image_url" id="edit_art_img" class="adm-input">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Executive Excerpt *</label>
                    <textarea name="excerpt" id="edit_art_exc" class="adm-textarea" rows="2" required></textarea>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Status</label>
                    <select name="status" id="edit_art_status" class="adm-select">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Save Publication</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-article').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_art_id').value = this.dataset.id;
        document.getElementById('edit_art_title').value = this.dataset.title;
        document.getElementById('edit_art_cat').value = this.dataset.cat;
        document.getElementById('edit_art_auth').value = this.dataset.auth;
        document.getElementById('edit_art_time').value = this.dataset.time;
        document.getElementById('edit_art_date').value = this.dataset.date;
        document.getElementById('edit_art_img').value = this.dataset.img;
        document.getElementById('edit_art_exc').value = this.dataset.exc;
        document.getElementById('edit_art_status').value = this.dataset.status;
        document.getElementById('modalEditArticle').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
