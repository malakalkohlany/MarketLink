<?php

$totalPages = (int) ($pagination['total_pages'] ?? 0);
$currentPage = (int) ($pagination['current_page'] ?? 1);
$totalItems = (int) ($pagination['total_items'] ?? 0);
$startItem = (int) ($pagination['start_item'] ?? 0);
$endItem = (int) ($pagination['end_item'] ?? 0);

if ($totalPages <= 1) {
    return;
}

$params = $_GET;

$paginationStart = max(1, $currentPage - 2);
$paginationEnd = min($totalPages, $currentPage + 2);

?>

<div class="admin-pagination">
    <div class="admin-pagination-info">
        Showing
        <strong><?= $startItem ?></strong>
        –
        <strong><?= $endItem ?></strong>
        of
        <strong><?= $totalItems ?></strong>
    </div>

    <div class="admin-pagination-controls">
        <?php if ($currentPage > 1): ?>
            <?php $params['page'] = $currentPage - 1; ?>
            <a
                href="?<?= e(http_build_query($params)) ?>"
                class="admin-pagination-arrow"
            >
                Previous
            </a>
        <?php else: ?>
            <span class="admin-pagination-arrow disabled">
                Previous
            </span>
        <?php endif; ?>

        <?php if ($paginationStart > 1): ?>
            <?php $params['page'] = 1; ?>
            <a
                href="?<?= e(http_build_query($params)) ?>"
                class="admin-pagination-number"
            >
                1
            </a>

            <?php if ($paginationStart > 2): ?>
                <span class="admin-pagination-dots">…</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($pageNumber = $paginationStart; $pageNumber <= $paginationEnd; $pageNumber++): ?>
            <?php if ($pageNumber === $currentPage): ?>
                <span
                    class="admin-pagination-number active"
                    aria-current="page"
                >
                    <?= $pageNumber ?>
                </span>
            <?php else: ?>
                <?php $params['page'] = $pageNumber; ?>
                <a
                    href="?<?= e(http_build_query($params)) ?>"
                    class="admin-pagination-number"
                >
                    <?= $pageNumber ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($paginationEnd < $totalPages): ?>
            <?php if ($paginationEnd < $totalPages - 1): ?>
                <span class="admin-pagination-dots">…</span>
            <?php endif; ?>

            <?php $params['page'] = $totalPages; ?>
            <a
                href="?<?= e(http_build_query($params)) ?>"
                class="admin-pagination-number"
            >
                <?= $totalPages ?>
            </a>
        <?php endif; ?>

        <?php if ($currentPage < $totalPages): ?>
            <?php $params['page'] = $currentPage + 1; ?>
            <a
                href="?<?= e(http_build_query($params)) ?>"
                class="admin-pagination-arrow"
            >
                Next
            </a>
        <?php else: ?>
            <span class="admin-pagination-arrow disabled">
                Next
            </span>
        <?php endif; ?>
    </div>
</div>