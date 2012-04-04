CREATE TABLE "comments" (
    "id" serial NOT NULL,
    "comment_type_id" int4 NOT NULL,

     -- MPTT-Related stuff
    "lft" int4 NOT NULL,
    "rgt" int4 NOT NULL,
    "lvl" int2 NOT NULL,
    "parent_id" int4,
    -- MPTT-End

    "scope" int4 NOT NULL,

    "user_id" int4 NOT NULL,
    "date" int4 DEFAULT EXTRACT( EPOCH FROM NOW()) NOT NULL,
    "text" text NOT NULL,

    --B8
    "state" varchar(16) DEFAULT 'queued' NOT NULL,
    "probability" numeric(6,5),
    --end B8

    PRIMARY KEY ("id"),
    FOREIGN KEY ("comment_type_id") REFERENCES "comment_types" ("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) WITH (OIDS=FALSE);
CREATE INDEX "comments_type_parent_idx" ON "comments" USING btree ("comment_type_id"  , "parent_id"  );

CREATE TABLE "comment_types" (
	"id" serial NOT NULL,
	"type" varchar(128) NOT NULL,
	PRIMARY KEY ("id")
) WITH (OIDS=FALSE);
