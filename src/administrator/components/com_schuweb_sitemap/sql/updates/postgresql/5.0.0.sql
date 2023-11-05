ALTER TABLE "#__schuweb_sitemap" 
DROP COLUMN IF EXISTS "count_xml",
DROP COLUMN IF EXISTS "count_html",
DROP COLUMN IF EXISTS "views_xml",
DROP COLUMN IF EXISTS "views_html",
DROP COLUMN IF EXISTS "lastvisit_xml",
DROP COLUMN IF EXISTS "lastvisit_html";