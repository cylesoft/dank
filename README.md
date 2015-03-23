# the dank platform

The internet seriously lacks publicly-accessible small communities that focus on broad flavors of content. We have every other extreme: sites like kottke.org and daring fireball that are one person's curated content, or old big sites like something awful that are hundreds of peoples' curated content, or channels within pinterest that are *everybody's* content uncurated, or subreddits that are tons of content messily "socially" curated, or individual blogs that are hyper-specialized content. There's room here for a better solution in the middle.

The dank platform intends to be one option in filling that gap. The dank platform's focus is on allowing a small community of curators to post content from around the internet and concentrate it in one place. 

## features

- Post some awesome content, whether it's Markdown-flavored text, images, gifs, videos, audio, links, whatever.
- Content intended for public visibility must be approved by other members of the site.
- Comment on other peoples' content.
- Text is fully parsed: use lame #hashtags and @mentions in your posts and comments.
- Text is super parsed: links to services like YouTube and Vimeo are expanded into embedded content.
- Actions are logged and users are notified of stuff like new comments and approved/rejected posts.
- New members require an invite code to register, by default.
- Subscribe to new posts via RSS.
- Site is built using pure functions. Maybe refactor to object-oriented sometime.

## dankest.website

The first implementation of this platform is [dankest.website](https://dankest.website/). We're gonna post some dank content on there; the vast majority of the features are intended for use on that site.

## server requirements

- lighttpd
- PHP 5.6+ (via php-fpm recommended)
- MySQL (MariaDB recommended)

## install

To install, clone the repo somewhere on a server. Point the site's web root to the `www` folder. Include `config/dank-lighty.conf` in your lighttpd config.

Rename `config/config.sample.php` to `config/config.php` and fill it out as necessary.

Rename `lib/dank/dbconn_mysql.sample.php` to `lib/dank/dbconn_mysql.php` and fill it out as necessary. Check out `dankestdb.sql` to build the database.

Fill out an issue here if you run into any problems.

## to do

- Needs a full nit-picky design/UX screening...
- Better mobile view...
- Notify users of @mentions...
- RSS feeds of hashtags and individual users...
- Custom permalinks for posts...
- TimeHop-style year-ago post in sidebar...
- Anonymous commenting...
- Host assets on Amazon S3?
- Cross-post to Twitter/Tumblr/whatever

## third-party credits

This site utilizes the awesome [PHP Markdown library](https://github.com/michelf/php-markdown) by Michel Fortin.