ALTER TABLE `categories` ADD `sort_order` integer DEFAULT 0 NOT NULL;--> statement-breakpoint
-- Homepage tile order for the seeded categories: upholstery first, curtains last. Admins can change it later.
UPDATE `categories` SET `sort_order` = CASE `slug`
  WHEN 'upholstery-fabrics' THEN 1
  WHEN 'cushions-accessories' THEN 2
  WHEN 'home-decor' THEN 3
  WHEN 'outdoor-fabrics' THEN 4
  WHEN 'curtain-fabrics' THEN 5
  ELSE `sort_order` END;
