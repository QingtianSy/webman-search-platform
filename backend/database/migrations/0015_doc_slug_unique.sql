-- 去重：保留每个 slug 中 id 最小的行，后续行 slug 加后缀
UPDATE docs_articles a
JOIN (
    SELECT slug, MIN(id) AS keep_id
    FROM docs_articles
    GROUP BY slug
    HAVING COUNT(*) > 1
) dup ON a.slug = dup.slug AND a.id != dup.keep_id
SET a.slug = CONCAT(a.slug, '-', a.id);

-- 添加唯一索引
ALTER TABLE docs_articles ADD UNIQUE INDEX uk_slug (slug);
