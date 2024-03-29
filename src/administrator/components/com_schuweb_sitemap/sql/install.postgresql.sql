CREATE TABLE "#__schuweb_sitemap" (
  "id" serial NOT NULL,
  "title" character varying(255) DEFAULT NULL,
  "alias" character varying(255) DEFAULT NULL,
  "introtext" text DEFAULT NULL,
  "metadesc" text DEFAULT NULL,
  "metakey" text DEFAULT NULL,
  "attribs" text DEFAULT NULL,
  "selections" text DEFAULT NULL,
  "excluded_items" text DEFAULT NULL,
  "is_default" integer DEFAULT 0,
  "state" integer DEFAULT NULL,
  "access" integer DEFAULT 0 NOT NULL,
  "created" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  PRIMARY KEY ("id")
);

CREATE TABLE "#__schuweb_sitemap_items" (
  "uid" character varying(100) NOT NULL,
  "itemid" integer NOT NULL,
  "view" character varying(10) NOT NULL,
  "sitemap_id" integer NOT NULL,
  "properties" varchar(300) DEFAULT NULL,
  PRIMARY KEY ("uid","itemid","view","sitemap_id")
);

CREATE INDEX "#__schuweb_sitemap_items_idx_uid" on "#__schuweb_sitemap_items" ("uid", "itemid");
CREATE INDEX "#__schuweb_sitemap_items_idx_view" on "#__schuweb_sitemap_items" ("view");
