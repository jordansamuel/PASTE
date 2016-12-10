--
-- Database schema for the manual installation of Paste 2.1
-- Default admin username/password - admin / admin - change once logged in
-- Also configure the Domain in admin/configure.php otherwise things won't display correctly.
--

-- Admin
CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `user` varchar(250) DEFAULT NULL,
  `pass` varchar(250) DEFAULT NULL
);

INSERT INTO `admin` (`id`, `user`, `pass`) VALUES
(1, 'admin', '$2y$10$qn1PmNaBfhrOmRuYfgclsO6tMsXpKquSjshvwqx/7BXFD2No6rpH2');

-- Admin history

CREATE TABLE `admin_history` (
  `id` int(11) NOT NULL,
  `last_date` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL
);

-- Ads
CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `text_ads` text,
  `ads_1` text,
  `ads_2` text
);

INSERT INTO ads (text_ads,ads_1,ads_2) VALUES ('','','');

CREATE TABLE `ban_user` (
  `id` int(11) NOT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `last_date` varchar(255) DEFAULT NULL
);

-- Captcha

CREATE TABLE `captcha` (
  `id` int(11) NOT NULL,
  `cap_e` varchar(255) DEFAULT NULL,
  `mode` varchar(255) DEFAULT NULL,
  `mul` varchar(255) DEFAULT NULL,
  `allowed` text,
  `color` mediumtext,
  `recaptcha_sitekey` text,
  `recaptcha_secretkey` text
);

INSERT INTO captcha (cap_e,mode,mul,allowed,color,recaptcha_sitekey,recaptcha_secretkey) VALUES ('off','Normal','off','ABCDEFGHIJKLMNOPQRSTUVYXYZabcdefghijklmnopqrstuvwxyz0123456789','#000000','','');

-- Interface

CREATE TABLE `interface` (
  `id` int(11) NOT NULL,
  `theme` text,
  `lang` text
);

INSERT INTO interface (theme,lang) VALUES ('default','en.php');

-- Mail

CREATE TABLE `mail` (
  `id` int(11) NOT NULL,
  `verification` text,
  `smtp_host` text,
  `smtp_username` text,
  `smtp_password` text,
  `smtp_port` text,
  `protocol` text,
  `auth` text,
  `socket` text
);

-- Pages

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `last_date` varchar(255) DEFAULT NULL,
  `page_name` varchar(255) DEFAULT NULL,
  `page_title` mediumtext,
  `page_content` longtext
);

-- Page views

CREATE TABLE `page_view` (
  `id` int(11) NOT NULL,
  `date` varchar(255) DEFAULT NULL,
  `tpage` varchar(255) DEFAULT NULL,
  `tvisit` varchar(255) DEFAULT NULL
);

-- Pastes

CREATE TABLE `pastes` (
  `id` int(11) NOT NULL,
  `title` text,
  `content` longtext,
  `encrypt` text,
  `password` text,
  `now_time` text,
  `s_date` text,
  `views` text,
  `ip` text,
  `date` text,
  `member` text,
  `expiry` text,
  `visible` text,
  `code` longtext
);

-- Sitemap

CREATE TABLE `sitemap_options` (
  `id` int(11) NOT NULL,
  `priority` varchar(255) DEFAULT NULL,
  `changefreq` varchar(255) DEFAULT NULL
);

INSERT INTO `sitemap_options` (`id`, `priority`, `changefreq`) VALUES
(1, '0.9', 'daily');

-- Site info

CREATE TABLE `site_info` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `des` mediumtext,
  `keyword` mediumtext,
  `site_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `twit` varchar(4000) DEFAULT NULL,
  `face` varchar(4000) DEFAULT NULL,
  `gplus` varchar(4000) DEFAULT NULL,
  `ga` varchar(255) DEFAULT NULL,
  `additional_scripts` text,
  `baseurl` text
);

INSERT INTO `site_info` (`id`, `title`, `des`, `keyword`, `site_name`, `email`, `twit`, `face`, `gplus`, `ga`, `additional_scripts`, `baseurl`) VALUES
(1, 'Paste', 'Paste can store text, source code or sensitive data for a set period of time.', 'paste,pastebin.com,pastebin,text,paste,online paste', 'Paste', '', 'https://twitter.com/', 'https://www.facebook.com/', 'https://plus.google.com/', 'UA-', '', 'pastethis.in');

-- Site permissions

CREATE TABLE `site_permissions` (
  `id` int(11) NOT NULL,
  `disableguest` varchar(255) DEFAULT NULL,
  `siteprivate` varchar(255) DEFAULT NULL
);

INSERT INTO `site_permissions` (`id`, `disableguest`, `siteprivate`) VALUES
(1, '', ''),
(2, 'off', 'off');

-- Users

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `oauth_uid` text,
  `username` text,
  `email_id` text,
  `full_name` text,
  `platform` text,
  `password` text,
  `verified` text,
  `picture` text,
  `date` text,
  `ip` text
);