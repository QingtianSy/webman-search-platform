CREATE TABLE IF NOT EXISTS docs_categories (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS docs_articles (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  category_id BIGINT NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  summary VARCHAR(500) NULL,
  content_md LONGTEXT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_docs_articles_category_id (category_id)
);
